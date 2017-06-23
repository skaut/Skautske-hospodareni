<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Factories\BankAccountForm;
use App\AccountancyModule\PaymentModule\Factories\IBankAccountFormFactory;
use Model\Payment\BankAccountNotFoundException;
use Model\Payment\BankAccountService;
use Nette\Application\BadRequestException;

class BankAccountsPresenter extends BasePresenter
{

    /** @var  IBankAccountFormFactory */
    private $formFactory;

    /** @var BankAccountService */
    private $accounts;

    /** @var int */
    private $id;


    public function __construct(IBankAccountFormFactory $formFactory, BankAccountService $accounts)
    {
        $this->formFactory = $formFactory;
        $this->accounts = $accounts;
    }

    public function handleAllowForSubunits(int $id): void
    {
        try {
            $this->accounts->allowForSubunits($id);
            $this->flashMessage('Bankovní účet zpřístupněn', 'success');
        } catch (BankAccountNotFoundException $e) {
            $this->flashMessage('Bankovní účet neexistuje', 'danger');
        }
        $this->redirect('this');
    }


    public function actionEdit(int $id): void
    {
        if($this->accounts->find($id) === NULL) {
            throw new BadRequestException('Bankovní účet neexistuje');
        }

        $this->id = $id;
    }


    public function renderDefault(): void
    {
        $this->template->accounts = $this->accounts->findByUnit($this->getUnitId());
    }


    protected function createComponentForm(): BankAccountForm
    {
        return $this->formFactory->create($this->id);
    }

}
