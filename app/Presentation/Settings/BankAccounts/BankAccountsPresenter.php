<?php

declare(strict_types=1);

namespace App\Presentation\Settings\BankAccounts;

use App\Components\Payment\BankAccountDetail\BankAccountDetailViewFactory;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingOutcome;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingService;
use App\Components\Payment\BankAccountForm;
use App\Components\Payment\GpcImportDialog;
use App\Components\Factories\Payment\IBankAccountFormFactory;
use App\Model\Auth\Resources\Unit as UnitResource;
use App\Model\Bank\BankTransactionAmountMismatch;
use App\Model\Bank\BankTransactionPairingNotAllowed;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\UnitId;
use App\Model\DTO\Payment\BankAccount;
use App\Model\Payment\BankAccount\BankAccountId;
use App\Model\Payment\BankAccountNotFound;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\ReadModel\Queries\CountGroupsWithBankAccountQuery;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Model\Unit\ReadModel\Queries\SubunitListQuery;
use App\Model\Unit\Unit;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\SkautisRole;
use App\Presentation\Settings\SettingsBasePresenter;
use InvalidArgumentException;
use Nette\Application\BadRequestException;

use function array_keys;
use function array_map;
use function array_reduce;

final class BankAccountsPresenter extends SettingsBasePresenter
{
    private ?int $id = null;

    public function __construct(
        private readonly IBankAccountFormFactory $formFactory,
        private readonly BankAccountService $accounts,
        private readonly BankAccountDetailViewFactory $detailViewFactory,
        private readonly BankAccountManualPairingService $manualPairingService,
    ) {
        parent::__construct();
    }

    public function handleAllowForSubunits(int $id): void
    {
        $this->assertCanEditBankAccount($id);

        try {
            $this->accounts->allowForSubunits($id);
            $this->flashMessage('Bankovní účet zpřístupněn', 'success');
        } catch (BankAccountNotFound) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }

        $this->redirect('this');
    }

    public function handleDisallowForSubunits(int $id): void
    {
        $this->assertCanEditBankAccount($id);

        try {
            $this->accounts->disallowForSubunits($id);
            $this->flashMessage('Bankovní účet znepřístupněn', 'success');
        } catch (BankAccountNotFound) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }

        $this->redirect('this');
    }

    public function handleRemove(int $id): void
    {
        $this->assertCanEditBankAccount($id);

        try {
            $this->accounts->removeBankAccount($id);
            $this->flashMessage('Bankovní účet byl odstraněn', 'success');
        } catch (BankAccountNotFound) {
        }

        $this->redirect('default', ['unitId' => $this->getUnitId()]);
    }

    public function handleImport(): void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        try {
            [$importedCount, $skautisCount, $existingCount] = $this->accounts->importFromSkautis($this->getCurrentUnitId()->toInt());
            $this->flashMessage(
                sprintf('Importováno %d nových účtů (Skautis vrátil %d, v DB bylo %d).', $importedCount, $skautisCount, $existingCount),
                'success',
            );
        } catch (BankAccountNotFound $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }

        $this->redirect('this');
    }

    public function actionPairTransactionToPayment(int $accountId, string $transactionKey, int $paymentId): void
    {
        $this->assertCanViewBankAccount($accountId);

        try {
            $outcome = $this->manualPairingService->pairTransactionToPayment(
                $accountId,
                $transactionKey,
                $paymentId,
                $this->getAccessibleGroupIds(),
                $this->userService->getUserDetail()->Person,
            );
            $this->flashManualPairingOutcome($outcome);
        } catch (BankTransactionAmountMismatch|BankTransactionPairingNotAllowed|InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
        }

        $this->redirect('detail', ['id' => $accountId, 'unitId' => $this->getUnitId()]);
    }

    public function actionPairTransactionToInvoice(int $accountId, string $transactionKey, int $invoiceId): void
    {
        $this->assertCanViewBankAccount($accountId);

        try {
            $outcome = $this->manualPairingService->pairTransactionToInvoice(
                $accountId,
                $transactionKey,
                $invoiceId,
                array_keys($this->unitService->getReadUnits($this->user)),
                $this->userService->getUserDetail()->Person,
            );
            $this->flashManualPairingOutcome($outcome);
        } catch (BankTransactionAmountMismatch|BankTransactionPairingNotAllowed|InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
        }

        $this->redirect('detail', ['id' => $accountId, 'unitId' => $this->getUnitId()]);
    }

    public function actionEdit(int $id): void
    {
        $this->assertCanEditBankAccount($id);
        $this->id = $id;
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->setSettingsTemplateParameters();
    }

    public function renderEdit(int $id): void
    {
        $this->template->setParameters([
            'account' => $this->findBankAccount($id),
            'groupsCount' => $this->queryBus->handle(new CountGroupsWithBankAccountQuery(new BankAccountId($id))),
            'invoiceSequencesCount' => $this->accounts->countInvoiceSequencesUsingBankAccount($id),
            'subunitGroupsCount' => $this->accounts->countGroupsDetachedByDisallowForSubunits($id),
            'subunitInvoiceSequencesCount' => $this->accounts->countInvoiceSequencesDetachedByDisallowForSubunits($id),
        ]);
    }

    public function actionDefault(): void
    {
        $accounts = $this->accounts->findByUnit($this->getCurrentUnitId());

        $this->template->setParameters([
            'accounts' => $accounts,
            'canEdit' => $this->canEdit(),
            'gpcImportableAccountIds' => $this->resolveGpcImportableAccountIds($accounts),
        ]);
    }

    public function actionDetail(int $id, ?int $paymentId = null, ?int $invoiceId = null): void
    {
        $this->assertCanViewBankAccount($id);
    }

    public function renderDetail(int $id, ?int $paymentId = null, ?int $invoiceId = null): void
    {
        $account = $this->accounts->find($id);
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));
        $groups = $this->queryBus->handle(new GetGroupList($readableUnitIds, false));
        $groupNames = [];

        foreach ($groups as $group) {
            $groupNames[$group->getId()] = $group->getName();
        }

        $detail = $this->detailViewFactory->create($id, $groupNames, $readableUnitIds, $paymentId, $invoiceId);

        $this->template->setParameters([
            'account' => $account,
            'groupNames' => $groupNames,
            'transactionRows' => $detail->transactionRows,
            'importBatches' => $detail->importBatches,
            'focusTargetLabel' => $detail->focusTargetLabel,
            'warningMessage' => $detail->warningMessage,
            'errorMessage' => $detail->errorMessage,
            'canImportGpc' => $this->canImportGpcForAccount($account),
        ]);
    }

    protected function createComponentForm(): BankAccountForm
    {
        return $this->formFactory->create($this->id);
    }

    protected function createComponentGpcImportDialog(): GpcImportDialog
    {
        $dialog = new GpcImportDialog(
            $this->accounts,
            $this->userService,
            fn (int $bankAccountId): bool => $this->canImportGpcForAccountId($bankAccountId),
        );

        $dialog->onSuccess[] = function (int $bankAccountId): void {
            if (! $this->isAjax()) {
                $this->redirect('this');
            }

            if ($this->getAction() === 'detail' && (int) $this->getParameter('id') === $bankAccountId) {
                $this->redrawControl('transactions');
                $this->redrawControl('importBatches');
            }
        };

        return $dialog;
    }

    private function noAccess(): void
    {
        $this->setView('accessDenied');
    }

    private function canEdit(?int $unitId = null): bool
    {
        return $this->authorizator->isAllowed(UnitResource::EDIT, $unitId ?? $this->getUnitId());
    }

    private function assertCanViewBankAccount(int $id): void
    {
        $account = $this->accounts->find($id);

        if ($account === null) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if ($this->canEdit($account->getUnitId())) {
            return;
        }

        if ($account->isAllowedForSubunits() && $this->isSubunitOf($account->getUnitId())) {
            return;
        }

        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        if (! $role instanceof SkautisRole) {
            throw new BadRequestException('Nepodařilo se ověřit roli uživatele');
        }

        if (
            $role->getUnitId() === $account->getUnitId()
            && $role->isBasicUnit()
            && ($role->isAccountant() || $role->isOfficer() || $role->isEventManager())
        ) {
            return;
        }

        $this->noAccess();
    }

    private function assertCanEditBankAccount(int $id): void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        $account = $this->findBankAccount($id);
        if ($account === null) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if ($this->canEdit($account->getUnitId())) {
            return;
        }

        $this->noAccess();
    }

    private function isSubunitOf(int $unitId): bool
    {
        $currentUnitId = $this->getCurrentUnitId()->toInt();

        foreach ($this->queryBus->handle(new SubunitListQuery(UnitId::fromInt($unitId))) as $subunit) {
            if (! $subunit instanceof Unit) {
                continue;
            }

            if ($subunit->getId() === $currentUnitId) {
                return true;
            }
        }

        return false;
    }

    private function findBankAccount(int $id): ?BankAccount
    {
        return $this->accounts->find($id);
    }

    /**
     * @param  BankAccount[]    $accounts
     * @return array<int, true>
     */
    private function resolveGpcImportableAccountIds(array $accounts): array
    {
        return array_reduce(
            $accounts,
            function (array $importableAccountIds, BankAccount $account): array {
                if ($this->canImportGpcForAccount($account)) {
                    $importableAccountIds[$account->getId()] = true;
                }

                return $importableAccountIds;
            },
            [],
        );
    }

    /** @return int[] */
    private function getAccessibleGroupIds(): array
    {
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));
        $groups = $this->queryBus->handle(new GetGroupList($readableUnitIds, false));

        return array_map(static fn ($group): int => $group->getId(), $groups);
    }

    private function flashManualPairingOutcome(BankAccountManualPairingOutcome $outcome): void
    {
        $this->flashMessage($outcome->successMessage, 'success');

        foreach ($outcome->warnings as $warning) {
            $this->flashMessage($warning, 'warning');
        }
    }

    private function canImportGpcForAccountId(int $id): bool
    {
        $account = $this->findBankAccount($id);

        return $account !== null && $this->canImportGpcForAccount($account);
    }

    private function canImportGpcForAccount(BankAccount $account): bool
    {
        return $this->canEdit($account->getUnitId())
            && $account->getTransactionSource()->value === BankTransactionSource::GPC->value;
    }
}
