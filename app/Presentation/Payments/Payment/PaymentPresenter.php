<?php

declare(strict_types=1);

namespace App\Presentation\Payments\Payment;

use App\Components\DataGrid;
use App\Components\Factories\Payment\IEmailButtonFactory;
use App\Components\Factories\Payment\IGroupUnitControlFactory;
use App\Components\Factories\Payment\IImportDialogFactory;
use App\Components\Factories\Payment\IMassAddFormFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
use App\Components\Factories\Payment\IPaymentDialogFactory;
use App\Components\Factories\Payment\IPaymentListFactory;
use App\Components\Factories\Payment\IPaymentNoteDialogFactory;
use App\Components\Factories\Payment\IRemoveGroupDialogFactory;
use App\Components\Factories\Payment\ISplitPaymentDialogFactory;
use App\Components\Payment\BankAccountDetail\BankAccountDetail;
use App\Components\Payment\BankAccountDetail\BankAccountDetailViewFactory;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingOutcome;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingService;
use App\Components\Payment\EmailButton;
use App\Components\Payment\GroupProgress;
use App\Components\Payment\GroupUnitControl;
use App\Components\Payment\ImportDialog;
use App\Components\Payment\MassAddForm;
use App\Components\Payment\PairButton;
use App\Components\Payment\PaymentDialog;
use App\Components\Payment\PaymentList;
use App\Components\Payment\PaymentNoteDialog;
use App\Components\Payment\RemoveGroupDialog;
use App\Components\Payment\SplitPaymentDialog;
use App\Http\ExcelResponse;
use App\Model\Bank\BankTransactionAmountMismatch;
use App\Model\Bank\BankTransactionPairingNotAllowed;
use App\Model\DTO\Payment\BankAccount as PaymentBankAccount;
use App\Model\DTO\Payment\Group as PaymentGroup;
use App\Model\DTO\Payment\Payment;
use App\Model\DTO\Payment\Person;
use App\Model\Excel\ExcelService;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\InvalidVariableSymbol;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use App\Model\Payment\ReadModel\Queries\PaymentListQuery;
use App\Model\Unit\UnitService;
use App\Model\User\Manager\PaymentGroupVisitManager;
use App\Presentation\Payments\PaymentsBasePresenter;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Skautis\Wsdl\PermissionException;

use function array_filter;
use function count;
use function date;
use function sprintf;
use function substr;

final class PaymentPresenter extends PaymentsBasePresenter
{
    /** @persistent */
    public int $id = 0;

    /** @persistent */
    public bool $directMemberOnly = true;

    /** @persistent */
    public bool $bankAccountTransactionsLoaded = false;

    /** @persistent */
    public ?int $bankAccountPaymentId = null;

    /** @var string[] */
    protected array $readUnits;

    /** @var Payment[] */
    protected array $payments = [];

    private ?BankAccountDetail $bankAccountDetail = null;

    private ?PaymentBankAccount $bankAccount = null;

    public function __construct(
        private PaymentService $model,
        protected UnitService $unitService,
        private ExcelService $excelService,
        private IMassAddFormFactory $massAddFormFactory,
        private IPairButtonFactory $pairButtonFactory,
        private IEmailButtonFactory $emailButtonFactory,
        private IGroupUnitControlFactory $unitControlFactory,
        private IRemoveGroupDialogFactory $removeGroupDialogFactory,
        private IPaymentDialogFactory $paymentDialogFactory,
        private IImportDialogFactory $importDialogFactory,
        private IPaymentNoteDialogFactory $paymentNoteDialogFactory,
        private IPaymentListFactory $paymentListFactory,
        private ISplitPaymentDialogFactory $splitPaymentDialogFactory,
        private PaymentGroupVisitManager $paymentGroupVisitManager,
        private BankAccountService $bankAccounts,
        private BankAccountDetailViewFactory $bankAccountDetailViewFactory,
        private BankAccountManualPairingService $bankAccountManualPairingService,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        $this->readUnits = $this->unitService->getReadUnits($this->user);
    }

    public function actionDefault(int $id): void
    {
        $this->assertCanEditGroup();
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->hasAccessToGroup($group)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění zobrazit detail plateb.']);

            return;
        }

        if ($this->canEditGroup($group)) {
            $this['pairButton']->setGroups([$id]);
        }

        $this->paymentGroupVisitManager->markVisited((int) $this->getUser()->getId(), $id);

        try {
            $nextVS = $this->model->getNextVS($group->getId());
        } catch (InvalidVariableSymbol $exception) {
            $this->flashMessage('Nelze vygenerovat následující VS: \''.$exception->getInvalidValue().'\'', 'danger');
            $nextVS = null;
        }

        $this->payments = $this->getPaymentsForGroup($id);
        if ($this->bankAccountTransactionsLoaded) {
            $this->refreshBankAccountTransactions($group);
        } else {
            $this->publishBankAccountTemplateParameters($group);
        }

        $this->template->setParameters([
            'group' => $group,
            'nextVS' => $nextVS,
            'payments' => $this->payments,
            'summarize' => $this->model->getGroupSummaries([$id])[$id],
            'now' => new DateTimeImmutable(),
            'notSentPaymentsCount' => $this->countNotSentPayments($this->payments),
        ]);
    }

    public function actionPairTransactionToPayment(int $id, int $accountId, string $transactionKey, ?int $paymentId = null): void
    {
        $group = $this->model->getGroup($id);
        if ($group === null || ! $this->canEditGroup($group)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění pracovat s touto skupinou.']);

            return;
        }

        if ($group->getBankAccountId() !== $accountId) {
            $this->flashMessage('Bankovní transakce nepatří k účtu této platební skupiny.', 'danger');
            $this->finishManualPairingRequest($group);

            return;
        }

        if ($paymentId === null) {
            $this->flashMessage('Pro ruční párování chybí cílová platba.', 'danger');
            $this->finishManualPairingRequest($group);

            return;
        }

        try {
            $outcome = $this->bankAccountManualPairingService->pairTransactionToPayment(
                $accountId,
                $transactionKey,
                $paymentId,
                [$id],
                $this->userService->getUserDetail()->Person,
            );
            $this->flashManualPairingOutcome($outcome);
        } catch (BankTransactionAmountMismatch|BankTransactionPairingNotAllowed|InvalidArgumentException $exception) {
            $this->flashMessage($exception->getMessage(), 'danger');
        }

        $this->finishManualPairingRequest($group);
    }

    public function handleLoadBankAccountTransactions(?int $paymentId = null): void
    {
        $group = $this->model->getGroup($this->id);
        if ($group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Nemáte oprávnění pracovat s touto skupinou.', 'danger');

            if ($this->isAjax()) {
                $this->redrawControl('flash');
            } else {
                $this->redirect('default', ['id' => $this->id]);
            }

            return;
        }

        if ($group->getBankAccountId() === null) {
            $this->flashMessage('Platební skupina nemá připojený bankovní účet.', 'warning');

            if ($this->isAjax()) {
                $this->redrawControl('flash');
            } else {
                $this->redirect('default', ['id' => $group->getId()]);
            }

            return;
        }

        $this->bankAccountTransactionsLoaded = true;
        $this->bankAccountPaymentId = $paymentId;
        $this->refreshBankAccountTransactions($group);

        if (! $this->isAjax()) {
            $this->redirect('default', ['id' => $group->getId()]);
        }

        $this->redrawControl('bankAccountTransactions');
        $this->redrawControl('bankAccountTransactionsToggle');
        $this->publishBankAccountTransactionsUrl();
    }

    public function handleHideBankAccountTransactions(): void
    {
        $group = $this->model->getGroup($this->id);
        if ($group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Nemáte oprávnění pracovat s touto skupinou.', 'danger');

            if ($this->isAjax()) {
                $this->redrawControl('flash');
            } else {
                $this->redirect('default', ['id' => $this->id]);
            }

            return;
        }

        $this->bankAccountTransactionsLoaded = false;
        $this->bankAccountPaymentId = null;
        $this->bankAccount = null;
        $this->bankAccountDetail = null;
        $this->publishBankAccountTemplateParameters($group);

        if (! $this->isAjax()) {
            $this->redirect('default', ['id' => $group->getId()]);
        }

        $this->redrawControl('bankAccountTransactions');
        $this->redrawControl('bankAccountTransactionsToggle');
        $this->publishBankAccountTransactionsUrl();
    }

    /** @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY */
    public function actionMassAdd(int $id, ?int $unitId = null, bool $directMemberOnly = true): void
    {
        $this->assertCanEditGroup();

        $group = $this->model->getGroup($id);

        $form = $this['massAddForm'];
        $list = $this->queryBus->handle(new MembersWithoutPaymentInGroupQuery($this->unitId, $id, $this->directMemberOnly));

        foreach ($list as $p) {
            if (! $p instanceof Person) {
                throw new LogicException('Assertion failed.');
            }
            $form->addPerson($p->getId(), $p->getEmails(), $p->getName());
        }

        $this->template->setParameters([
            'unitPairs' => $this->readUnits,
            'group' => $group,
            'id' => $this->id,
            'showForm' => count($list) !== 0,
            'directMemberOnly' => $this->directMemberOnly,
        ]);
    }

    private function assertCanEditGroup(): void
    {
        $group = $this->model->getGroup($this->id);

        if ($group !== null && $this->canEditGroup($group)) {
            return;
        }

        $this->setView('accessDenied');
        $this->template->setParameters(['message' => 'Nemáte oprávnění pracovat s touto skupinou.']);
    }

    public function handleGenerateVs(): void
    {
        $this->assertCanEditGroup();

        try {
            $nextVS = $this->model->getNextVS($this->id);
            if ($nextVS === null) {
                $this->flashMessage('Vyplňte VS libovolné platbě a další pak již budou dogenerovány způsobem +1.', 'warning');
                $this->redirect('this');
            }

            $numberOfUpdatedVS = $this->model->generateVs($this->id);
            $this->flashMessage('Počet dogenerovaných VS: '.$numberOfUpdatedVS, 'success');
            $this->redirect('this');
        } catch (InvalidVariableSymbol $exception) {
            $this->flashMessage('Nelze vygenerovat následující VS: \''.$exception->getInvalidValue().'\'', 'danger');
            $this->redirect('this');
        }
    }

    public function handleExport(int $id): void
    {
        $this->assertCanEditGroup();

        $group = $this->model->getGroup($id);
        $payments = $this->getPaymentsForGroup($id);
        $groupName = substr(Strings::webalize($group->getName(), null, false), 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);

        try {
            $spreadsheet = $this->excelService->getPaymentsList($payments, $groupName);
            $this->flashMessage('Seznam plateb byl exportován');
            $this->sendResponse(new ExcelResponse(Strings::webalize($group->getName()).'-'.date('Y_n_j'), $spreadsheet));
        } catch (PermissionException $e) {
            $this->flashMessage('Nemáte oprávnění k exportu platební skupiny! ('.$e->getMessage().')', 'danger');
            $this->redirect('default');
        } catch (Exception $e) {
            $this->flashMessage('Nepodařilo se vygenerovat excel');
            $this->logger->error(sprintf('Failed to generate excel (%s: %s)', $e::class, $e->getMessage()), ['exception' => $e]);
            $this->redirect('this');
        }
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function handleCloseGroup(): void
    {
        $this->assertCanEditGroup();

        $userData = $this->userService->getUserDetail();
        $note = 'Uživatel '.$userData->Person.' uzavřel skupinu plateb dne '.date('j.n.Y H:i');

        try {
            $this->model->closeGroup($this->id, $note);
        } catch (GroupNotFound) {
        }

        $this->redirect('this');
    }

    public function handleOpenGroup(): void
    {
        $this->assertCanEditGroup();

        $userData = $this->userService->getUserDetail();
        $note = 'Uživatel '.$userData->Person.' otevřel skupinu plateb dne '.date('j.n.Y H:i');

        try {
            $this->model->openGroup($this->id, $note);
        } catch (GroupNotFound) {
        }

        $this->redirect('this');
    }

    public function handleOpenRemoveDialog(): void
    {
        $dialog = $this['removeGroupDialog'];
        $dialog->open();
    }

    protected function createComponentPaymentDialog(): PaymentDialog
    {
        $this->assertCanEditGroup();

        $dialog = $this->paymentDialogFactory->create($this->id);

        $dialog->onSuccess[] = function (): void {
            $this->redrawPaymentGrid();
        };

        return $dialog;
    }

    protected function createComponentImportDialog(): ImportDialog
    {
        $this->assertCanEditGroup();
        $dialog = $this->importDialogFactory->create($this->id);
        $dialog->onSuccess[] = function (): void {
            $this->redrawPaymentGrid();
        };

        return $dialog;
    }

    protected function createComponentPaymentNoteDialog(): PaymentNoteDialog
    {
        $this->assertCanEditGroup();

        $dialog = $this->paymentNoteDialogFactory->create($this->id);

        $dialog->onSuccess[] = function (): void {
            $this->redrawPaymentGrid();
        };

        return $dialog;
    }

    protected function createComponentPaymentList(): PaymentList
    {
        $paymentList = $this->paymentListFactory->create($this->id, $this->isEditable);
        $paymentList->setPayments($this->payments);
        $paymentList->onChange[] = function (): void {
            $this->redrawPaymentAndBankAccountGrids();
        };

        return $paymentList;
    }

    protected function createComponentBankAccountTransactionsGrid(): DataGrid
    {
        return $this->bankAccountDetailViewFactory->createTransactionsGrid(
            $this->bankAccountDetail?->transactionRows ?? [],
            true,
            true,
        );
    }

    protected function createComponentSplitPaymentDialog(): SplitPaymentDialog
    {
        $this->assertCanEditGroup();

        $dialog = $this->splitPaymentDialogFactory->create($this->id);
        $dialog->onSuccess[] = function (): void {
            $this->redrawPaymentGrid();
        };

        return $dialog;
    }

    protected function createComponentPairButton(): PairButton
    {
        $pairButton = $this->pairButtonFactory->create();
        $pairButton->enableAjax();
        $pairButton->onSuccess[] = function (): void {
            $this->redrawPaymentAndBankAccountGrids();
        };

        return $pairButton;
    }

    protected function createComponentEmailButton(): EmailButton
    {
        $group = $this->model->getGroup($this->id);

        return $this->emailButtonFactory->create($this->isEditable, $this->payments, $group);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }

    protected function createComponentUnit(): GroupUnitControl
    {
        return $this->unitControlFactory->create($this->id);
    }

    protected function createComponentRemoveGroupDialog(): RemoveGroupDialog
    {
        $group = $this->model->getGroup($this->id);

        return $this->removeGroupDialogFactory->create($this->id, $group !== null && $this->canEditGroup($group));
    }

    protected function createComponentProgress(): GroupProgress
    {
        return new GroupProgress($this->model->getGroupSummaries([$this->id])[$this->id]);
    }

    /** @return Payment[] */
    private function getPaymentsForGroup(int $groupId): array
    {
        try {
            return $this->queryBus->handle(new PaymentListQuery($groupId));
        } catch (InvalidVariableSymbol $exception) {
            $this->flashMessage(
                'Některá platba má neplatný variabilní symbol: '.$exception->getInvalidValue().'. Platby nelze zobrazit.',
                'warning',
            );

            return [];
        }
    }

    private function redrawPaymentGrid(): void
    {
        $this->payments = $this->getPaymentsForGroup($this->id);
        $paymentList = $this['paymentList'];
        if (! $paymentList instanceof PaymentList) {
            throw new LogicException('Assertion failed.');
        }
        $paymentList->setPayments($this->payments);
        $this->redrawControl('grid');
        $this->redrawControl('groupProgress');
    }

    private function redrawPaymentAndBankAccountGrids(): void
    {
        $this->redrawPaymentGrid();

        if ($this->bankAccountTransactionsLoaded) {
            $group = $this->model->getGroup($this->id);
            if ($group !== null) {
                $this->refreshBankAccountTransactions($group);
                $this->redrawControl('bankAccountTransactions');
            }
        }

        $this->redrawControl('flash');
        $this->publishBankAccountTransactionsUrl();
    }

    private function finishManualPairingRequest(PaymentGroup $group): void
    {
        $this->bankAccountTransactionsLoaded = true;

        if (! $this->isAjax()) {
            $this->redirect('default', ['id' => $group->getId()]);
        }

        $this->setView('default');
        $this->redrawPaymentAndBankAccountGrids();
    }

    private function refreshBankAccountTransactions(PaymentGroup $group): void
    {
        $this->bankAccount = null;
        $this->bankAccountDetail = null;

        $bankAccountId = $group->getBankAccountId();
        if ($bankAccountId !== null) {
            $this->bankAccount = $this->bankAccounts->find($bankAccountId);
            $this->bankAccountDetail = $this->bankAccount === null
                ? new BankAccountDetail(null, [], errorMessage: 'Připojený bankovní účet již neexistuje.')
                : $this->bankAccountDetailViewFactory->createForPaymentGroup(
                    $this->bankAccount->getId(),
                    $group->getId(),
                    $group->getName(),
                    $this->bankAccountPaymentId,
                );
        }

        $this->publishBankAccountTemplateParameters($group);
    }

    private function publishBankAccountTemplateParameters(PaymentGroup $group): void
    {
        $this->template->setParameters([
            'hasBankAccount' => $group->getBankAccountId() !== null,
            'bankAccountTransactionsLoaded' => $this->bankAccountTransactionsLoaded,
            'bankAccountPaymentId' => $this->bankAccountPaymentId,
            'bankAccount' => $this->bankAccount,
            'bankAccountDetail' => $this->bankAccountDetail,
        ]);
    }

    private function publishBankAccountTransactionsUrl(): void
    {
        if (! $this->isAjax()) {
            return;
        }

        $this->payload->url = $this->link('default', ['id' => $this->id]);
        $this->payload->postGet = true;
    }

    private function flashManualPairingOutcome(BankAccountManualPairingOutcome $outcome): void
    {
        $this->flashMessage($outcome->successMessage, 'success');
        foreach ($outcome->warnings as $warning) {
            $this->flashMessage($warning, 'warning');
        }
    }

    /** @param Payment[] $payments */
    private function countNotSentPayments(array $payments): int
    {
        return count(array_filter($payments, fn (Payment $payment) => $payment->getSentEmails() === [] && $payment->getState()->equalsValue(State::PREPARING)));
    }
}
