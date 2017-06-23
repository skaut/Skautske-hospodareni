<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Factories\BankAccountForm;
use App\AccountancyModule\PaymentModule\Factories\IBankAccountFormFactory;
use Model\Payment\BankAccountService;

class BankAccountsPresenter extends BasePresenter
{

    /** @var  IBankAccountFormFactory */
    private $formFactory;

    /** @var BankAccountService */
    private $accounts;


    public function __construct(IBankAccountFormFactory $formFactory, BankAccountService $accounts)
    {
        $this->formFactory = $formFactory;
        $this->accounts = $accounts;
    }


    public function renderDefault(): void
    {
        $this->template->accounts = $this->accounts->findByUnit($this->getUnitId());
    }


    protected function createComponentForm(): BankAccountForm
    {
        return $this->formFactory->create(NULL);
    }

}
