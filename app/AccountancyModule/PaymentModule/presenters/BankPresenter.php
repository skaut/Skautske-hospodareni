<?php

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankPresenter extends BasePresenter
{

    /** @var \Model\BankService */
    protected $bank;

    public function __construct(\Model\PaymentService $paymentService, \Model\BankService $bankService)
    {
        parent::__construct($paymentService);
        $this->bank = $bankService;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->errMsg = [];
    }

    public function actionDefault() : void
    {
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

    public function createComponentTokenForm() : Form
    {
        $form = new BaseForm();
        $form->addText("token", "API Token");
        $form->addText("daysback", "Počet dní kontrolovaných nazpět")
            ->setDefaultValue(14);
        $form->addSubmit('send', 'Nastavit')
            ->setAttribute("class", "btn btn-primary");

        $form->onSubmit[] = function(Form $form) : void {
            $this->tokenFormSubmitted($form);
        };

        return $form;
    }

    private function tokenFormSubmitted(Form $form) : void
    {
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
