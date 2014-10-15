<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentPresenter extends BasePresenter {

    /**
     *
     * @var \Model\PaymentService
     */
    protected $payments;
    protected $notFinalStates;

    public function __construct(\Model\PaymentService $paymentService) {
        parent::__construct();
        $this->payments = $paymentService;
    }

    protected function startup() {
        parent::startup();
        //Kontrola ověření přístupu
        $this->template->notFinalStates = $this->notFinalStates = $this->payments->getNonFinalStates();
    }

    public function renderDefault($onlyOpen = 1) {
        $this->template->onlyOpen = $onlyOpen;
        $this->template->groups = $this->payments->getGroups((bool) $onlyOpen);
        $this->template->payments = $this->payments->getAll();
    }

    public function renderDetail($id) {
        //ověření přístupu
        $this->template->group = $group = $this->payments->getGroup($id);
        if ($group === FALSE) {
            $this->flashMessage("Neplatný požadavek na detail skupiny plateb", "fail");
            $this->redirect("default");
        }
        $form = $this['paymentForm'];
        $form->addSubmit('send', 'Přidat platbu')->setAttribute("class", "btn btn-primary");
        $form->setDefaults(array(
            'amount' => $group['amount'],
            'maturity' => $group['maturity'],
            'ks' => $group['ks'],
            'oid' => $group['id'],
        ));

        $this->template->payments = $this->payments->getAll($id);
        $this->template->summarize = $this->payments->summarizeByState($id);
    }

    public function renderEdit($paymentId) {
        $payment = $this->payments->get($paymentId);
        $form = $this['paymentForm'];
        $form->addSubmit('send', 'Přidat platbu')->setAttribute("class", "btn btn-primary");
        $form->setDefaults(array(
            'name' => $payment->name,
            'email' => $payment->email,
            'amount' => $payment->amount,
            'maturity' => $payment->maturity,
            'vs' => $payment->vs,
            'ks' => $payment->ks,
            'note' => $payment->note,
            'oid' => $payment->groupId,
            'pid' => $payment->id,
        ));
    }

    public function handleCancel($paymentId) {
        //ověření přístupu
        if ($this->payments->cancel($paymentId, array("state" => "canceled"))) {
            $this->flashMessage("Platba byla zrušena.");
        } else {
            $this->flashMessage("Platbu se nepodařilo uzavřít!", "fail");
        }
        $this->redirect("this");
    }

    public function handleSend($paymentId) {
        //ověření přístupu
        if ($this->payments->sendInfo($this->template, $paymentId, $this->context->unitService->getDetail()->ID)) {
            $this->flashMessage("Informační email byl odeslán.");
        } else {
            $this->flashMessage("Informační email se nepodařilo odeslat!", "fail");
        }
        $this->redirect("this");
    }

    public function handleSendGroup($id) {
        //ověření přístupu
        $payments = $this->payments->getAll($id);
        $unitId = $this->context->unitService->getDetail()->ID;
        $cnt = 0;
        foreach ($payments as $p) {
            $cnt += $this->payments->sendInfo($this->template, $p->id, $unitId);
        }

        if ($cnt > 0) {
            $this->flashMessage("Informační emaily($cnt) byly odeslány.");
        } else {
            $this->flashMessage("Nebyl odeslán žádný informační email!", "fail");
        }
        $this->redirect("this");
    }

    public function handleComplete($paymentId) {
        //ověření přístupu
        if ($this->payments->update($paymentId, array("state" => "completed"))) {
            $this->flashMessage("Platba byla zaplacena.");
        } else {
            $this->flashMessage("Platbu se nepodařilo uzavřít!", "fail");
        }
        $this->redirect("this");
    }

    public function createComponentPaymentForm($name) {
        $form = new Form($this, $name);
        $form->addText("name", "Název/účel")
                ->addRule(Form::FILLED, "Musíte zadat název platby");
        $form->addText("amount", "Částka")
                ->addRule(Form::FILLED, "Musíte vyplnit částku")
                ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo");
        $form->addText("email", "Email")
                ->addCondition(Form::FILLED)
                ->addRule(Form::EMAIL, "Zadaný email nemá platný formát");
        $form->addDatePicker("maturity", "Splatnost");
        $form->addText("vs", "VS")
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, "Variabilní symbol musí být číslo");
        $form->addText("ks", "KS")
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addText("note", "Poznámka");
        $form->addHidden("oid");
        $form->addHidden("pid");
        $form->onSubmit[] = array($this, 'paymentSubmitted');
        return $form;
    }

    function paymentSubmitted(Form $form) {
        //ověření přístupu
        $v = $form->getValues();
        if ($v->maturity == NULL) {
            $form['maturity']->addError("Musíte vyplnit splatnost");
            return;
        }
        if ($v->pid != "") {//EDIT
            if ($this->payments->update($v->pid, array('name' => $v->name, 'email' => $v->email, 'amount' => $v->amount, 'maturity' => $v->maturity, 'vs' => $v->vs, 'ks' => $v->ks, 'note' => $v->note))) {
                $this->flashMessage("Platba byla upravena");
            } else {
                $this->flashMessage("Platbu se nepodařilo založit", "fail");
            }
        } else {//ADD
            if ($this->payments->createPayment($v->oid, $v->name, $v->email, $v->amount, NULL, $v->maturity, $v->vs, $v->ks, $v->note)) {
                $this->flashMessage("Platba byla přidána");
            } else {
                $this->flashMessage("Platbu se nepodařilo založit", "fail");
            }
        }
        $this->redirect("detail", array("id" => $v->oid));
    }

}
