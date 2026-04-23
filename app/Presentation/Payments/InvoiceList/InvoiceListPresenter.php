<?php

declare(strict_types=1);

namespace App\Presentation\Payments\InvoiceList;

use App\Components\DataGrid;
use App\Components\Factories\Payment\IInvoiceCashPaymentDialogFactory;
use App\Components\Factories\Payment\IInvoiceDuplicateDialogFactory;
use App\Components\Factories\Payment\IInvoiceFormFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
use App\Components\Grids\GridFactory;
use App\Components\Payment;
use App\Components\Payment\PairButton;
use App\Model\Common\UserNotFound;
use App\Model\Export\ExportService;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Invoice\EmailTemplateNotSet;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\InvoiceAlreadySent;
use App\Model\Invoice\InvoiceHasNoEmails;
use App\Model\Invoice\InvoiceMailingService;
use App\Model\Invoice\InvoiceReminderNotAllowed;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Services\PdfRenderer;
use App\Presentation\Payments\PaymentsBasePresenter;
use InvalidArgumentException;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Throwable;

use function array_keys;
use function array_map;
use function in_array;
use function pathinfo;
use function strtolower;

final class InvoiceListPresenter extends PaymentsBasePresenter
{
    protected ?int $groupId = null;
    protected ?InvoiceSequence $invoiceSequence = null;
    private bool $createMode = false;
    private ?Invoice $editedInvoice = null;
    /** @var array<int, string|null> */
    private array $sequenceEmailDisabledReasons = [];

    public function __construct(
        private readonly GridFactory $gridFactory,
        protected InvoiceManager $invoiceManager,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceSequenceRepository $invoiceSequenceRepository,
        protected IInvoiceFormFactory $invoiceFormFactory,
        protected IInvoiceCashPaymentDialogFactory $invoiceCashPaymentDialogFactory,
        protected IInvoiceDuplicateDialogFactory $invoiceDuplicateDialogFactory,
        private readonly IPairButtonFactory $pairButtonFactory,
        protected ExportService $exportService,
        protected PdfRenderer $pdf,
        protected InvoiceMailingService $invoiceMailingService,
        protected IGoogleRepository $googleRepository,
    ) {
        parent::__construct();
    }

    public function actionDefault(?int $invoiceSequenceId = null): void
    {
        $editableSequenceIds = array_map(
            static fn (array $sequence): int => (int) $sequence['id'],
            $this->invoiceSequenceRepository->getGridByUnits($this->getEditableUnits()),
        );

        if ($invoiceSequenceId === null) {
            if ($editableSequenceIds !== []) {
                $this['pairButton']->setSequences($editableSequenceIds);
            }

            $this->template->setParameters([
                'invoiceSequence' => null,
                'invoiceSequences' => $this->invoiceSequenceRepository->getGridByUnits($this->getReadableUnitIds()),
                'canPairInvoices' => $editableSequenceIds !== [],
                'editableSequenceIds' => $editableSequenceIds,
                'isSequenceContext' => false,
            ]);

            return;
        }

        $invoiceSequence = $this->invoiceSequenceRepository->findAccessibleByUnits($invoiceSequenceId, $this->getReadableUnitIds());
        if ($invoiceSequence === null) {
            $this->flashMessage('Fakturační řada nebyla nalezena', 'danger');
            $this->redirect('default');
        }

        $this->invoiceSequence = $invoiceSequence;
        if (in_array($invoiceSequence->getId(), $editableSequenceIds, true)) {
            $this['pairButton']->setSequences([$invoiceSequence->getId()]);
        }

        $this->template->setParameters([
            'invoiceSequence' => $invoiceSequence,
            'canPairInvoices' => in_array($invoiceSequence->getId(), $editableSequenceIds, true),
            'invoiceSequences' => $this->invoiceSequenceRepository->getGridByUnits($this->getReadableUnitIds()),
            'editableSequenceIds' => $editableSequenceIds,
            'isSequenceContext' => true,
        ]);
    }

    public function actionEdit(int $id): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getEditableUnits());
        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        if (! $invoice->canBeEdited()) {
            $this->flashMessage('Upravit lze pouze fakturu ve stavu Vystavená.', 'warning');
            $this->redirect('detail', ['id' => $invoice->getId()]);
        }

        $this->editedInvoice = $invoice;
        $this->invoiceSequence = $invoice->getSequence();
        $this->template->setParameters([
            'invoice' => $invoice,
        ]);
    }

    public function actionCreate(int $invoiceSequenceId): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->findAccessibleByUnits($invoiceSequenceId, $this->getEditableUnits());
        if (! $invoiceSequence instanceof InvoiceSequence) {
            $this->flashMessage('Fakturační řada nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        $this->invoiceSequence = $invoiceSequence;
        $this->createMode = true;
        $this->template->setParameters([
            'invoiceSequence' => $invoiceSequence,
        ]);
    }

    public function actionDetail(int $id): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        $this->invoiceSequence = $invoice->getSequence();
    }

    public function renderDetail(int $id): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        $stampImageSrc = $this->exportService->getInvoiceStampImagePath($invoice) === null
            ? null
            : $this->link('stampImage!', ['id' => $invoice->getId()]);
        $logoImageSrc = $this->exportService->getInvoiceLogoImagePath($invoice) === null
            ? null
            : $this->link('logoImage!', ['id' => $invoice->getId()]);

        $this->template->setParameters([
            'invoice' => $invoice,
            'invoicePreviewHtml' => $this->exportService->getInvoice($invoice, $stampImageSrc, $logoImageSrc),
        ]);
    }

    protected function createComponentCreateForm(): Payment\InvoiceForm
    {
        if (! $this->invoiceSequence instanceof InvoiceSequence) {
            throw new InvalidArgumentException('Formulář faktury lze vytvořit jen v kontextu konkrétní řady.');
        }

        return $this->invoiceFormFactory->create($this->invoiceSequence);
    }

    protected function createComponentEditForm(): Payment\InvoiceForm
    {
        if (! $this->invoiceSequence instanceof InvoiceSequence || ! $this->editedInvoice instanceof Invoice) {
            throw new InvalidArgumentException('Editační formulář faktury lze vytvořit jen v kontextu konkrétní faktury.');
        }

        return $this->invoiceFormFactory->create($this->invoiceSequence, $this->editedInvoice);
    }

    protected function createComponentCashPaymentDialog(): Payment\InvoiceCashPaymentDialog
    {
        $dialog = $this->invoiceCashPaymentDialogFactory->create($this->invoiceSequence?->getId());

        $dialog->onSuccess[] = function (): void {
            $this->redrawControl('grid');
        };

        return $dialog;
    }

    protected function createComponentDuplicateInvoiceDialog(): Payment\InvoiceDuplicateDialog
    {
        return $this->invoiceDuplicateDialogFactory->create(
            $this->invoiceSequence?->getId(),
            $this->getEditableUnits(),
        );
    }

    protected function createComponentPairButton(): PairButton
    {
        return $this->pairButtonFactory->create();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/grid.latte',
            [
                'showOpenSequenceAction' => ! $this->invoiceSequence instanceof InvoiceSequence,
                'enableCashPaymentDialog' => true,
                'enableInvoiceDuplicateDialog' => $this->invoiceSequence instanceof InvoiceSequence,
            ],
        );

        $grid->addColumnNumber('id', 'Id')
             ->setSortable()
             ->setFilterText();
        if (! $this->invoiceSequence instanceof InvoiceSequence) {
            $grid->addColumnText('sequenceDisplayLabel', 'Řada');
        }
        $grid->addColumnText('invoiceNumber', 'Číslo faktury')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('variable_symbol', 'VS')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('customerDisplayName', 'Název')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('recipientsString', 'E-maily')
            ->addCellAttributes(['class' => 'small']);
        $grid->addColumnText('dueDate', 'Datum splatnosti')
            ->setSortable();
        $grid->addColumnText('dateOfIssue', 'Datum vystavení')
            ->setSortable();
        $grid->addColumnText('issuedBy', 'Vystavil')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('stateLabel', 'Stav');

        $grid->addColumnText('actions', 'Akce');

        $grid->addFilterText('search', '', ['invoiceNumber', 'variableSymbol', 'customer.name', 'issuedBy'])
            ->setPlaceholder('Hledej...');

        $grid->setDataSource(
            $this->invoiceSequence instanceof InvoiceSequence
                ? $this->invoiceRepository->getGrid($this->invoiceSequence)
                : $this->invoiceRepository->getGridByUnits($this->getReadableUnitIds()),
        );

        return $grid;
    }

    public function renderCreate(int $invoiceSequenceId): void
    {
        if (! $this->invoiceSequence instanceof InvoiceSequence || ! $this->createMode) {
            $this->flashMessage('Fakturační řada nebyla nalezena.', 'danger');
            $this->redirect('default');
        }
    }

    public function handleDownloadPdf(int $id): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        try {
            $template = $this->exportService->getInvoice($invoice);
        } catch (Throwable $e) {
            $this->flashMessage('Nepodařilo se vygenerovat PDF faktury.', 'danger');
            $this->redirectAfterInvoiceAction($invoice);

            return;
        }

        $this->pdf->render($template, $invoice->getInvoiceNumber().'.pdf');
        $this->terminate();
    }

    public function handleStampImage(int $id): void
    {
        $this->sendInvoiceImage($id, 'stamp');
    }

    public function handleLogoImage(int $id): void
    {
        $this->sendInvoiceImage($id, 'logo');
    }

    private function sendInvoiceImage(int $id, string $type): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            throw new BadRequestException('Faktura nebyla nalezena.', 404);
        }

        $imagePath = $type === 'logo'
            ? $this->exportService->getInvoiceLogoImagePath($invoice)
            : $this->exportService->getInvoiceStampImagePath($invoice);

        if ($imagePath === null) {
            throw new BadRequestException($type === 'logo' ? 'Logo nebylo nalezeno.' : 'Razítko nebylo nalezeno.', 404);
        }

        $mimeType = match (strtolower((string) pathinfo($imagePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        $this->sendResponse(new FileResponse($imagePath, $type === 'logo' ? 'logo' : 'razitko-podpis', $mimeType, false));
    }

    public function handleRemove(int $id): void
    {
    }

    public function handleSendEmail(int $id): void
    {
        $this->sendInvoiceEmail($id, EmailType::get(EmailType::INVOICE_INFO), 'Faktura byla odeslána.', false);
    }

    public function handleMarkAsDelivered(int $id): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        $marked = $this->invoiceManager->markAsDelivered($invoice);

        $this->flashMessage(
            $marked ? 'Předání faktury bylo potvrzeno.' : 'Faktura už byla předána.',
            $marked ? 'success' : 'info',
        );

        $this->redirectAfterInvoiceAction($invoice);
    }

    public function handleResendEmail(int $id): void
    {
        $this->sendInvoiceEmail($id, EmailType::get(EmailType::INVOICE_INFO), 'Faktura byla znovu odeslána.', true);
    }

    public function handleSendReminder(int $id): void
    {
        $this->sendInvoiceEmail($id, EmailType::get(EmailType::INVOICE_REMINDER), 'Upomínka byla odeslána.', true);
    }

    private function sendInvoiceEmail(int $id, EmailType $type, string $successMessage, bool $allowResend): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        try {
            $this->invoiceMailingService->sendEmail($id, $type, $allowResend);
            $this->flashMessage($successMessage, 'success');
        } catch (Throwable $e) {
            $this->flashMessage($this->resolveMailingError($e, $type), 'danger');
        }

        $this->redirectAfterInvoiceAction($invoice);
    }

    private function resolveMailingError(Throwable $e, EmailType $type): string
    {
        if ($e instanceof InvalidOAuth) {
            return $e->getExplainedMessage();
        }

        if ($e instanceof OAuthNotSet) {
            return 'Fakturační řada nemá nastavený e-mail odesílatele.';
        }

        if ($e instanceof EmailTemplateNotSet) {
            return $type->equalsValue(EmailType::INVOICE_REMINDER)
                ? 'Fakturační řada nemá nastavenou aktivní šablonu pro upomínku.'
                : 'Fakturační řada nemá nastavenou aktivní šablonu pro odeslání faktury.';
        }

        if ($e instanceof InvoiceHasNoEmails || $e instanceof InvoiceAlreadySent || $e instanceof InvoiceReminderNotAllowed || $e instanceof InvalidArgumentException) {
            return $e->getMessage();
        }

        if ($e instanceof UserNotFound) {
            return 'Nepodařilo se určit aktuálního uživatele pro audit odeslání.';
        }

        return 'Odeslání e-mailu se nepodařilo.';
    }

    /** @return int[] */
    private function getReadableUnitIds(): array
    {
        return array_keys($this->unitService->getReadUnits($this->user));
    }

    public function getInvoiceEmailDisabledReason(Invoice $invoice): ?string
    {
        if (! $invoice->hasEmailRecipients()) {
            return 'Faktura nemá vyplněné příjemce.';
        }

        return $this->getSequenceEmailDisabledReason($invoice->getSequence());
    }

    public function canSendInvoiceEmail(Invoice $invoice): bool
    {
        return $this->getInvoiceEmailDisabledReason($invoice) === null;
    }

    private function redirectAfterInvoiceAction(Invoice $invoice): void
    {
        if ($this->invoiceSequence instanceof InvoiceSequence) {
            $this->redirect('this', ['invoiceSequenceId' => $this->invoiceSequence->getId()]);
        }

        $this->redirect('default');
    }

    private function getSequenceEmailDisabledReason(InvoiceSequence $sequence): ?string
    {
        $sequenceId = $sequence->getId();
        if (array_key_exists($sequenceId, $this->sequenceEmailDisabledReasons)) {
            return $this->sequenceEmailDisabledReasons[$sequenceId];
        }

        $oauthId = $sequence->getOauthId();
        if ($oauthId === null) {
            return $this->sequenceEmailDisabledReasons[$sequenceId] = 'Fakturační řada nemá nastavený e-mail odesílatele.';
        }

        try {
            $this->googleRepository->find($oauthId);

            return $this->sequenceEmailDisabledReasons[$sequenceId] = null;
        } catch (Throwable) {
            return $this->sequenceEmailDisabledReasons[$sequenceId] = 'Nastavený e-mail odesílatele už není v systému dostupný.';
        }
    }
}
