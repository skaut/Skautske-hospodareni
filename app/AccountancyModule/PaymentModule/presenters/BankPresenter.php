<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankPresenter extends BasePresenter {

    /**
     *
     * @var \Model\BankService
     */
    protected $bank;

    public function __construct(\Model\PaymentService $paymentService, \Model\BankService $bankService) {
        parent::__construct($paymentService);
        $this->bank = $bankService;
    }

    protected function startup() {
        parent::startup();
        $this->template->errMsg = array();
    }

    public function actionDefault() {
        $this->template->token = $token = $this->bank->getToken($this->aid);
        $this['tokenForm']['token']->setDefaultValue($token);
        if ($token) {
            try {
                $this->template->transactions = $this->bank->getTransactionsFio($token, date("Y-m-d", strtotime("-14 day")));
            } catch (\Model\BankTimeoutException $exc) {
                $this->template->errMsg[] = "Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.";
            } catch (\Model\BankTimeLimitException $exc) {
                $this->template->errMsg[] = "Mezi dotazy na bankovnictví musí být prodleva 1 minuta!";
            }
        }
    }

    public function createComponentTokenForm($name) {
        $form = new Form($this, $name);
        $form->addText("token", "API Token");
        $form->addSubmit('send', 'Nastavit')
                ->setAttribute("class", "btn btn-primary");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function tokenFormSubmitted(Form $form) {
        if (!$this->isEditable) {
            $this->flashMessage("Nejste oprávněni k úpravám tokenu!", "error");
            $this->redirect("this");
        }
        $v = $form->getValues();

        if ($this->bank->setToken($this->aid, $v->token)) {
            $this->flashMessage("Token byl nastaven");
        } else {
            $this->flashMessage("Token se nepodařilo nastavit", "error");
        }
        $this->redirect("this");
    }

}
