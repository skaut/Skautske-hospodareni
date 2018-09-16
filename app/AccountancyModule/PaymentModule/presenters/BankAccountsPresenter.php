<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Factories\BankAccountForm;
use App\AccountancyModule\PaymentModule\Factories\IBankAccountFormFactory;
use Model\Auth\Resources\Unit;
use Model\BankTimeLimit;
use Model\BankTimeout;
use Model\DTO\Payment\BankAccount;
use Model\Payment\BankAccountNotFound;
use Model\Payment\BankAccountService;
use Model\Payment\TokenNotSet;
use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Nette\Application\BadRequestException;
use function array_map;
use function in_array;

class BankAccountsPresenter extends BasePresenter
{
    /** @var  IBankAccountFormFactory */
    private $formFactory;

    /** @var BankAccountService */
    private $accounts;

    /** @var int[] */
    private $accountIds = [];

    /** @var int */
    private $id;

    public function __construct(IBankAccountFormFactory $formFactory, BankAccountService $accounts)
    {
        parent::__construct();
        $this->formFactory = $formFactory;
        $this->accounts    = $accounts;
    }

    public function handleAllowForSubunits(int $id) : void
    {
        if (! $this->canEdit() || ! in_array($id, $this->accountIds, true)) {
            $this->noAccess();
        }

        try {
            $this->accounts->allowForSubunits($id);
            $this->flashMessage('Bankovní účet zpřístupněn', 'success');
        } catch (BankAccountNotFound $e) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }
        $this->redirect('this');
    }

    public function handleDisallowForSubunits(int $id) : void
    {
        if (! $this->canEdit() || ! in_array($id, $this->accountIds, true)) {
            $this->noAccess();
        }

        try {
            $this->accounts->disallowForSubunits($id);
            $this->flashMessage('Bankovní účet znepřístupněn', 'success');
        } catch (BankAccountNotFound $e) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }
        $this->redirect('this');
    }

    public function handleRemove(int $id) : void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        try {
            $this->accounts->removeBankAccount($id);
            $this->flashMessage('Bankovní účet byl odstraněn', 'success');
        } catch (BankAccountNotFound $e) {
        }
        $this->redirect('this');
    }


    public function handleImport() : void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        try {
            $this->accounts->importFromSkautis($this->getUnitId());
            $this->flashMessage('Účty byly importovány', 'success');
        } catch (BankAccountNotFound $e) {
            $this->flashMessage('Nenalezeny žádné účty', 'warning');
        }

        $this->redirect('this');
    }


    public function actionEdit(int $id) : void
    {
        if (! $this->canEdit()) {
            $this->noAccess();
        }

        $account = $this->accounts->find($id);

        if ($account === null) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if (! $this->canEdit($account->getUnitId())) {
            $this->noAccess();
        }

        $this->id = $id;
    }

    public function actionDefault() : void
    {
        $accounts         = $this->accounts->findByUnit($this->getCurrentUnitId());
        $this->accountIds = array_map(
            function (BankAccount $a) {
                return $a->getId();
            },
            $accounts
        );

        $this->template->accounts = $accounts;
        $this->template->canEdit  = $this->canEdit();
    }


    public function renderDetail(int $id) : void
    {
        $account = $this->accounts->find($id);

        if ($account === null) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if (! $this->canViewBankAccount($account)) {
            $this->noAccess();
        }

        $this->template->account      = $account;
        $this->template->transactions = null;
        try {
            $this->template->transactions = $this->accounts->getTransactions($id, 60);
        } catch (TokenNotSet $e) {
            $this->template->warningMessage = 'Nemáte vyplněný token pro komunikaci s FIO';
        } catch (BankTimeLimit $e) {
            $this->template->warningMessage = PairButton::TIME_LIMIT_MESSAGE;
        } catch (BankTimeout $e) {
            $this->template->errorMessage = PairButton::TIMEOUT_MESSAGE;
        }
    }


    protected function createComponentForm() : BankAccountForm
    {
        return $this->formFactory->create($this->id);
    }


    private function noAccess() : void
    {
        $this->flashMessage('Na tuto stránku nemáte přistup', 'danger');
        $this->redirect(403, 'default');
    }

    private function canEdit(?int $unitId = null) : bool
    {
        return $this->authorizator->isAllowed(Unit::EDIT, $unitId ?? $this->getUnitId());
    }

    private function canViewBankAccount(BankAccount $account) : bool
    {
        if ($this->canEdit($account->getUnitId())) {
            return true;
        }

        if ($account->isAllowedForSubunits() && $this->isSubunitOf($account->getUnitId())) {
            return true;
        }

        /** @var SkautisRole $role */
        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());

        return $role->getUnitId() === $account->getUnitId() && $role->isBasicUnit() && $role->isAccountant();
    }

    private function isSubunitOf(int $unitId) : bool
    {
        $currentUnitId = $this->getCurrentUnitId();
        $subunits      = $this->unitService->getSubunits($unitId);

        foreach ($subunits as $subunit) {
            if ($subunit->getId() === $currentUnitId) {
                return true;
            }
        }

        return false;
    }
}
