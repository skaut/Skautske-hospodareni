<?php

declare(strict_types=1);

namespace App\Presentation\Payments\InvoiceList;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Components\Payment;
use App\Components\Factories\Payment\IInvoiceCashPaymentDialogFactory;
use App\Components\Factories\Payment\IInvoiceFormFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
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
use Http\Discovery\Exception\NotFoundException;
use InvalidArgumentException;
use Nette\Utils\ArrayHash;
use Throwable;

use function array_keys;
use function array_map;
use function in_array;

final class InvoiceListPresenter extends PaymentsBasePresenter
{
    protected ?int $groupId = null;
    protected ?InvoiceSequence $invoiceSequence = null;
    /** @var array<int, string|null> */
    private array $sequenceEmailDisabledReasons = [];

    public function __construct(
        private readonly GridFactory $gridFactory,
        protected InvoiceManager $invoiceManager,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceSequenceRepository $invoiceSequenceRepository,
        protected IInvoiceFormFactory $invoiceFormFactory,
        protected IInvoiceCashPaymentDialogFactory $invoiceCashPaymentDialogFactory,
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

    public function renderEdit(int $id): void
    {
        $this->redirect('detail', ['id' => $id]);
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

        $data = [
            'supplier' => [
                'name' => $invoice->getSupplier()->getName(),
                'street' => $invoice->getSupplier()->getAddress()->getStreet(),
                'city' => $invoice->getSupplier()->getAddress()->getCity(),
                'zip' => $invoice->getSupplier()->getAddress()->getZipCode(),
                'country' => 'Česká republika',
                'ic' => $invoice->getSupplier()->getCompanyNumber(),
                'mobil' => $invoice->getSupplier()->getPhone() ?? $invoice->getSequence()->getPhone(),
                'email' => $this->resolveSenderEmail($invoice),
                'bankName' => $invoice->getBankName(),
                'bankAccount' => $invoice->getAccountNumber()?->getNumberWithPrefixAndBankCode(),
                'iban' => $invoice->getIban(),
                'bic' => $invoice->getBic(),
            ],
            'customer' => [
                'name' => $invoice->getCustomer()->getDisplayName(),
                'address' => $invoice->getCustomer()->getDisplayAddress(),
                'ic' => $invoice->getCustomer()->getCompanyNumber(),
                'dic' => $invoice->getCustomer()->getVatNumber(),
                'hasCompanyNumber' => $invoice->getCustomer()->hasCompanyNumber(),
                'hasVatNumber' => $invoice->getCustomer()->hasVatNumber(),
                'isAnonymous' => $invoice->getCustomer()->isAnonymous(),
            ],
            'invoice' => [
                'number' => $invoice->getInvoiceNumber(),
                'variableSymbol' => $invoice->getVariableSymbol(),
                'constantSymbol' => '0008',
                'specificSymbol' => '',
                'paymentMethod' => $invoice->getPaymentType(),
                'dateIssued' => $invoice->getDateOfIssue(),
                'dateDue' => $invoice->getDueDate(),
                'items' => $invoice->getItems(),
                'totalAmount' => $invoice->getTotalAmount(),
                'deposits' => 0.00,
                'amountDue' => $invoice->getTotalAmount(),
            ],
            'user' => [
                'name' => $invoice->getIssuedBy(),
            ],
        ];

        $param = ArrayHash::from($data);

        $this->template->setParameters((array) $param);
    }

    protected function createComponentCreateForm(): Payment\InvoiceForm
    {
        if (! $this->invoiceSequence instanceof InvoiceSequence) {
            throw new InvalidArgumentException('Formulář faktury lze vytvořit jen v kontextu konkrétní řady.');
        }

        return $this->invoiceFormFactory->create($this->invoiceSequence);
    }

    protected function createComponentCashPaymentDialog(): Payment\InvoiceCashPaymentDialog
    {
        if (! $this->invoiceSequence instanceof InvoiceSequence) {
            throw new InvalidArgumentException('Dialog hotovostní úhrady lze otevřít jen v kontextu konkrétní řady.');
        }

        $dialog = $this->invoiceCashPaymentDialogFactory->create($this->invoiceSequence->getId());

        $dialog->onSuccess[] = function (): void {
            $this->redrawControl('grid');
        };

        return $dialog;
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
                'enableCashPaymentDialog' => $this->invoiceSequence instanceof InvoiceSequence,
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

    public function handleDownloadPdf(int $id): void
    {
        $invoice = $this->invoiceRepository->findAccessibleByUnits($id, $this->getReadableUnitIds());

        if (! $invoice instanceof Invoice) {
            $this->flashMessage('Faktura nebyla nalezena.', 'danger');
            $this->redirect('default');
        }

        try {
            $template = $this->exportService->getInvoice($invoice);
            $this->pdf->render($template, $invoice->getInvoiceNumber().'.pdf');
            $this->terminate();
        } catch (NotFoundException $e) {
            $this->flashMessage('Nepodařilo se vygenerovat PDF faktury.', 'danger');
            $this->redirectAfterInvoiceAction($invoice);
        }
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

    private function resolveSenderEmail(Invoice $invoice): ?string
    {
        $oauthId = $invoice->getSequence()->getOauthId();
        if ($oauthId === null) {
            return null;
        }

        try {
            return $this->googleRepository->find($oauthId)->getEmail();
        } catch (Throwable) {
            return null;
        }
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
