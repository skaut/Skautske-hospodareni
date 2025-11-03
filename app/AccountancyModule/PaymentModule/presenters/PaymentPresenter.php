<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\ExcelResponse;
use App\AccountancyModule\PaymentModule\Components\EmailButton;
use App\AccountancyModule\PaymentModule\Components\GroupProgress;
use App\AccountancyModule\PaymentModule\Components\GroupUnitControl;
use App\AccountancyModule\PaymentModule\Components\ImportDialog;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Components\PaymentDialog;
use App\AccountancyModule\PaymentModule\Components\PaymentList;
use App\AccountancyModule\PaymentModule\Components\PaymentNoteDialog;
use App\AccountancyModule\PaymentModule\Components\RemoveGroupDialog;
use App\AccountancyModule\PaymentModule\Factories\IEmailButtonFactory;
use App\AccountancyModule\PaymentModule\Factories\IGroupUnitControlFactory;
use App\AccountancyModule\PaymentModule\Factories\IImportDialogFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IPairButtonFactory;
use App\AccountancyModule\PaymentModule\Factories\IPaymentDialogFactory;
use App\AccountancyModule\PaymentModule\Factories\IPaymentListFactory;
use App\AccountancyModule\PaymentModule\Factories\IPaymentNoteDialogFactory;
use App\AccountancyModule\PaymentModule\Factories\IRemoveGroupDialogFactory;
use DateTimeImmutable;
use Model\DTO\Payment\Payment;
use Model\DTO\Payment\Person;
use Model\ExcelService;
use Model\Payment\GroupNotFound;
use Model\Payment\InvalidVariableSymbol;
use Model\Payment\Payment\State;
use Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\PaymentService;
use Model\UnitService;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Skautis\Wsdl\PermissionException;

use function array_filter;
use function assert;
use function count;
use function date;
use function sprintf;
use function substr;

class PaymentPresenter extends BasePresenter
{
    /** @persistent */
    public int $id = 0;

    /** @persistent */
    public bool $directMemberOnly = true;

    /** @var string[] */
    protected array $readUnits;

    /** @var Payment[] */
    protected array $payments;

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
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        // Kontrola ověření přístupu
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

        try {
            $nextVS = $this->model->getNextVS($group->getId());
        } catch (InvalidVariableSymbol $exception) {
            $this->flashMessage('Nelze vygenerovat následující VS: \''.$exception->getInvalidValue().'\'', 'danger');
            $nextVS = null;
        }

        $this->payments = $this->getPaymentsForGroup($id);

        $this->template->setParameters([
            'group' => $group,
            'nextVS' => $nextVS,
            'payments' => $this->payments,
            'summarize' => $this->model->getGroupSummaries([$id])[$id],
            'now' => new DateTimeImmutable(),
            'notSentPaymentsCount' => $this->countNotSentPayments($this->payments),
        ]);
    }

    /** @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY */
    public function actionMassAdd(int $id, ?int $unitId = null, bool $directMemberOnly = true): void
    {
        $this->assertCanEditGroup();

        $group = $this->model->getGroup($id);

        $form = $this['massAddForm'];
        $list = $this->queryBus->handle(new MembersWithoutPaymentInGroupQuery($this->unitId, $id, $this->directMemberOnly));

        foreach ($list as $p) {
            assert($p instanceof Person);

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
            $this->redrawControl('grid');
        };

        return $dialog;
    }

    protected function createComponentImportDialog(): ImportDialog
    {
        $this->assertCanEditGroup();
        $dialog = $this->importDialogFactory->create($this->id);
        $dialog->onSuccess[] = function (): void {
            $this->redrawControl('grid');
        };

        return $dialog;
    }

    protected function createComponentPaymentNoteDialog(): PaymentNoteDialog
    {
        $this->assertCanEditGroup();

        $dialog = $this->paymentNoteDialogFactory->create($this->id);

        $dialog->onSuccess[] = function (): void {
            $this->redrawControl('grid');
        };

        return $dialog;
    }

    protected function createComponentPaymentList(): PaymentList
    {
        return $this->paymentListFactory->create($this->id, $this->isEditable);
    }

    protected function createComponentPairButton(): PairButton
    {
        return $this->pairButtonFactory->create();
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
        return $this->queryBus->handle(new PaymentListQuery($groupId));
    }

    /** @param Payment[] $payments */
    private function countNotSentPayments(array $payments): int
    {
        return count(array_filter($payments, fn (Payment $payment) => $payment->getSentEmails() === [] && $payment->getState()->equalsValue(State::PREPARING)));
    }
}
