<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\Auth\IAuthorizator;
use App\AccountancyModule\Auth\Unit;
use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Factories\BankAccountForm;
use App\AccountancyModule\PaymentModule\Factories\IBankAccountFormFactory;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Model\DTO\Payment\BankAccount;
use Model\Payment\BankAccountNotFoundException;
use Model\Payment\BankAccountService;
use Model\Payment\TokenNotSetException;
use Nette\Application\BadRequestException;

class BankAccountsPresenter extends BasePresenter
{

    /** @var  IBankAccountFormFactory */
    private $formFactory;

    /** @var BankAccountService */
    private $accounts;

    /** @var int[] */
    private $accountIds = [];

    /** @var IAuthorizator */
    private $authorizator;

    /** @var int */
    private $id;


    public function __construct(IBankAccountFormFactory $formFactory, BankAccountService $accounts, IAuthorizator $authorizator)
    {
        parent::__construct();
        $this->formFactory = $formFactory;
        $this->accounts = $accounts;
        $this->authorizator = $authorizator;
    }

    public function handleAllowForSubunits(int $id): void
    {
        if (!$this->canEdit() || !in_array($id, $this->accountIds, TRUE)) {
            $this->noAccess();
        }

        try {
            $this->accounts->allowForSubunits($id);
            $this->flashMessage('Bankovní účet zpřístupněn', 'success');
        } catch (BankAccountNotFoundException $e) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }
        $this->redirect('this');
    }

    public function handleDisallowForSubunits(int $id): void
    {
        if (!$this->canEdit() || !in_array($id, $this->accountIds, TRUE)) {
            $this->noAccess();
        }

        try {
            $this->accounts->disallowForSubunits($id);
            $this->flashMessage('Bankovní účet znepřístupněn', 'success');
        } catch (BankAccountNotFoundException $e) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }
        $this->redirect('this');
    }

    public function handleRemove(int $id): void
    {
        if (!$this->canEdit()) {
            $this->noAccess();
        }

        try {
            $this->accounts->removeBankAccount($id);
            $this->flashMessage('Bankovní účet byl odstraněn', 'success');
        } catch(BankAccountNotFoundException $e) {
        }
        $this->redirect('this');
    }


    public function handleImport(): void
    {
        if (!$this->canEdit()) {
            $this->noAccess();
        }

        try {
            $this->accounts->importFromSkautis($this->getUnitId());
            $this->flashMessage('Účty byly importovány', 'success');
        } catch (BankAccountNotFoundException $e) {
            $this->flashMessage('Nenalezeny žádné účty', 'warning');
        }

        $this->redirect('this');
    }


    public function actionEdit(int $id): void
    {
        if (!$this->canEdit()) {
            $this->noAccess();
        }

        $account = $this->accounts->find($id);

        if( ! $this->canEdit($account->getUnitId())) {
            $this->noAccess();
        }

        if ($account === NULL) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        $this->id = $id;
    }

    public function actionDefault(): void
    {
        $accounts = $this->accounts->findByUnit($this->getCurrentUnitId());
        $this->accountIds = array_map(function (BankAccount $a) { return $a->getId(); }, $accounts);

        $this->template->accounts = $accounts;
        $this->template->canEdit = $this->canEdit();
    }


    public function renderDetail(int $id): void
    {
        $account = $this->accounts->find($id);

        if($account === NULL) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        if(!$this->canEdit($account->getUnitId()) && ( ! $account->isAllowedForSubunits() || ! $this->isSubunitOf($account->getUnitId()))) {
            $this->noAccess();
        }

        $this->template->account = $account;
        $this->template->transactions = NULL;
        try {
            $this->template->transactions = $this->accounts->getTransactions($id, 60);
        } catch(TokenNotSetException $e) {
            $this->template->warningMessage = 'Nemáte vyplněný token pro komunikaci s FIO';
        } catch (BankTimeLimitException $e) {
            $this->template->warningMessage = PairButton::TIME_LIMIT_MESSAGE;
        } catch (BankTimeoutException $e) {
            $this->template->errorMessage = PairButton::TIMEOUT_MESSAGE;
        }
    }


    protected function createComponentForm(): BankAccountForm
    {
        return $this->formFactory->create($this->id);
    }


    private function noAccess(): void
    {
        $this->flashMessage('Na tuto stránku nemáte přistup', 'danger');
        $this->redirect(403, 'default');
    }

    private function canEdit(int $unitId = NULL): bool
    {
        return $this->authorizator->isAllowed(Unit::EDIT, $unitId ?? $this->getUnitId());
    }

    private function isSubunitOf(int $unitId): bool
    {
        $currentUnitId = $this->getCurrentUnitId();
        $subunits = $this->unitService->getSubunits($unitId);

        foreach($subunits as $subunit) {
            if($subunit->getId() === $currentUnitId) {
                return TRUE;
            }
        }

        return FALSE;
    }

}
