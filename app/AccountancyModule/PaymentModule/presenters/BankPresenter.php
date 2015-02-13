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
        $this->template->bankInfo = $bankInfo = $this->bank->getInfo($this->aid);
        if ($bankInfo) {
            $this['tokenForm']->setDefaults($bankInfo);
            try {
                $this->template->transactions = array_reverse($this->bank->getTransactionsFio($bankInfo->token, $bankInfo->daysback));
            } catch (\Model\BankTimeoutException $exc) {
                $this->template->errMsg[] = "Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.";
            } catch (\Model\BankTimeLimitException $exc) {
                $this->template->errMsg[] = "Mezi dotazy na bankovnictví musí být prodleva 1 minuta!";
            }
        }
    }

    public function createComponentTokenForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addText("token", "API Token");
        $form->addText("daysback", "Počet dní kontrolovaných nazpět");
        $form->addSubmit('send', 'Nastavit')
                ->setAttribute("class", "btn btn-primary");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function tokenFormSubmitted(Form $form) {
        if (!$this->isEditable) {
            $this->flashMessage("Nejste oprávněni k úpravám tokenu!", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();

        if ($this->bank->setToken($this->aid, $v->token, $v->daysback)) {
            $this->flashMessage("Token byl nastaven");
        } else {
            $this->flashMessage("Token se nepodařilo nastavit", "danger");
        }
        $this->redirect("this");
    }

}
