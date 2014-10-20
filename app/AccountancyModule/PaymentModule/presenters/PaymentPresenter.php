<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentPresenter extends BasePresenter {

    protected $notFinalStates;
    protected $groups;

    protected function startup() {
        parent::startup();
        //Kontrola ověření přístupu
        $this->template->notFinalStates = $this->notFinalStates = $this->model->getNonFinalStates();
        $this->groups = $this->model->getGroups($this->objectId);
    }

    public function renderDefault($onlyOpen = 1) {
        $this->template->onlyOpen = $onlyOpen;
        $this->template->groups = $onlyOpen ? array_filter($this->groups, create_function('$o', 'return $o->state == "open";')) : $this->groups;
        $this->template->payments = $this->model->getAll(array_keys($this->groups));
    }

    public function renderDetail($id) {
        //ověření přístupu
        if (!array_key_exists($id, $this->groups)) {
            $this->flashMessage("Neplatný požadavek na detail skupiny plateb", "fail");
            $this->redirect("default");
        }
        $this->template->group = $group = $this->groups[$id];
        $form = $this['paymentForm'];
        $form->addSubmit('send', 'Přidat platbu')->setAttribute("class", "btn btn-primary");
        $form->setDefaults(array(
            'amount' => $group['amount'],
            'maturity' => $group['maturity'],
            'ks' => $group['ks'],
            'oid' => $group['id'],
        ));

        $this->template->payments = $payments = $this->model->getAll($id);
        $this->template->summarize = $this->model->summarizeByState($id);
        $paymentsForSendEmail = array_filter($payments, create_function('$p', 'return strlen($p->email)>4 && $p->state == "preparing";'));
        $this->template->isGroupSendActive = ($group->state == 'open') && count($paymentsForSendEmail) > 0;
    }

    public function renderEdit($pid) {
        //ověření přístupu
        $payment = $this->model->get($this->objectId, $pid);
        $form = $this['paymentForm'];
        $form->addSubmit('send', 'Přidat')->setAttribute("class", "btn btn-primary");
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
        $this->template->linkBack = $this->link("detail", array("id" => $payment->groupId));
    }

    public function handleCancel($id) {
        //ověření přístupu
        if (!$this->model->get($this->objectId, $id)) {
            $this->flashMessage("Neplatný požadavek na zrušení platby!", "error");
            $this->redirect("this");
        }
        if ($this->model->update($id, array("state" => "canceled"))) {
            $this->flashMessage("Platba byla zrušena.");
        } else {
            $this->flashMessage("Platbu se nepodařilo uzavřít!", "fail");
        }
        $this->redirect("this");
    }

    public function handleSend($id) {
        //ověření přístupu
        if (!$this->model->get($this->objectId, $id)) {
            $this->flashMessage("Neplatný požadavek na odeslání emailu!", "error");
            $this->redirect("this");
        }
        if ($this->model->sendInfo($this->objectId, $this->template, $id, $this->aid)) {
            $this->flashMessage("Informační email byl odeslán.");
        } else {
            $this->flashMessage("Informační email se nepodařilo odeslat!", "fail");
        }
        $this->redirect("this");
    }

    /**
     * rozešle všechny neposlané emaily
     * @param int $id groupId
     */
    public function handleSendGroup($id) {
        //ověření přístupu
        if (!array_key_exists($id, $this->groups)) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "fail");
            $this->redirect("this");
        }
        $payments = $this->model->getAll($id);
        $cnt = 0;
        foreach ($payments as $p) {
            $cnt += $this->model->sendInfo($this->objectId, $this->template, $p->id, $this->aid);
        }

        if ($cnt > 0) {
            $this->flashMessage("Informační emaily($cnt) byly odeslány.");
        } else {
            $this->flashMessage("Nebyl odeslán žádný informační email!", "fail");
        }
        $this->redirect("this");
    }

    public function handleComplete($id) {
        //ověření přístupu
        if (!$this->model->get($this->objectId, $id)) {
            $this->flashMessage("Neplatný požadavek na uzavření platby!", "error");
            $this->redirect("this");
        }
        if ($this->model->update($id, array("state" => "completed"))) {
            $this->flashMessage("Platba byla zaplacena.");
        } else {
            $this->flashMessage("Platbu se nepodařilo uzavřít!", "fail");
        }
        $this->redirect("this");
    }

    public function handlePairPayments($id = NULL) {
        if ($id !== NULL && !array_key_exists($id, $this->groups)) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "fail");
            $this->redirect("this");
        }
        $pairsCnt = $this->model->pairPayments($this->objectId, $id);
        if ($pairsCnt > 0) {
            $this->flashMessage("Podařilo se spárovat platby ($pairsCnt)");
        } else {
            $this->flashMessage("Žádné platby nebyly spárovány");
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
        $form->addText("vs", "VS", NULL, 10)
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, "Variabilní symbol musí být číslo");
        $form->addText("ks", "KS", NULL, 10)
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
            if ($this->model->update($v->pid, array('name' => $v->name, 'email' => $v->email, 'amount' => $v->amount, 'maturity' => $v->maturity, 'vs' => $v->vs, 'ks' => $v->ks, 'note' => $v->note))) {
                $this->flashMessage("Platba byla upravena");
            } else {
                $this->flashMessage("Platbu se nepodařilo založit", "fail");
            }
        } else {//ADD
            if ($this->model->createPayment($v->oid, $v->name, $v->email, $v->amount, $v->maturity, NULL, $v->vs, $v->ks, $v->note)) {
                $this->flashMessage("Platba byla přidána");
            } else {
                $this->flashMessage("Platbu se nepodařilo založit", "fail");
            }
        }
        $this->redirect("detail", array("id" => $v->oid));
    }

}
