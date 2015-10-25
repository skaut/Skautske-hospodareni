<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentPresenter extends BasePresenter {

    protected $notFinalStates;

    /**
     *
     * @var \Model\BankService
     */
    protected $bank;
    protected $readUnits;
    protected $editUnits;
    protected $unitService;

    public function __construct(\Model\PaymentService $paymentService, \Model\BankService $bankService, \Model\UnitService $unitService) {
        parent::__construct($paymentService);
        $this->bank = $bankService;
        $this->unitService = $unitService;
    }

    protected function startup() {
        parent::startup();
        //Kontrola ověření přístupu
        $this->template->notFinalStates = $this->notFinalStates = $this->model->getNonFinalStates();
        //$this->groups = $this->model->getGroupsIn($this->user->getIdentity()->access['read']);
        $this->readUnits = $this->unitService->getReadUnits($this->user);
        $this->editUnits = $this->unitService->getEditUnits($this->user);
    }

    public function renderDefault($onlyOpen = 1) {
        $this->template->onlyOpen = $onlyOpen;
        $groups = $this->model->getGroups(array_keys($this->readUnits), $onlyOpen);
        foreach ($groups as $gid => $g) {
            $groups[$gid]['sumarize'] = $this->model->summarizeByState($gid);
        }
        $this->template->groups = $groups;
        $this->template->payments = $this->model->getAll(array_keys($groups), TRUE);
    }

    public function renderDetail($id) {
        $this->template->units = $this->readUnits;
        $this->template->group = $group = $this->model->getGroup(array_keys($this->readUnits), $id);
        if (!$group) {
            $this->flashMessage("Nemáte oprávnění zobrazit detail plateb", "warning");
            $this->redirect("Payment:default");
        }
        $this->template->maxVS = $maxVS = $this->model->getMaxVS($group['id']);
        $form = $this['paymentForm'];
        $form->setDefaults(array(
            'amount' => $group['amount'],
            'maturity' => $group['maturity'],
            'ks' => $group['ks'],
            'oid' => $group['id'],
            'vs' => $maxVS != NULL ? $maxVS + 1 : "",
        ));

        $this->template->payments = $payments = $this->model->getAll($id);
        $this->template->summarize = $this->model->summarizeByState($id);
        $paymentsForSendEmail = array_filter($payments, create_function('$p', 'return strlen($p->email)>4 && $p->state == "preparing";'));
        $this->template->isGroupSendActive = ($group->state == 'open') && count($paymentsForSendEmail) > 0;
    }

    public function renderEdit($pid) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění editovat platbu", "warning");
            $this->redirect("Payment:default");
        }
        $payment = $this->model->get(array_keys($this->editUnits), $pid);
        $form = $this['paymentForm'];
        $form['send']->caption = "Upravit";
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

    public function actionMassAdd($id) {
        //ověření přístupu
        $this->template->unitPairs = $this->readUnits;
        $this->template->detail = $detail = $this->model->getGroup(array_keys($this->readUnits), $id);
        $this->template->list = $list = $this->model->getPersons($this->aid, $id); //@todo:?nahradit aid za array_keys($this->editUnits) ??

        if (!$detail) {
            $this->flashMessage("Neplatný požadavek na přehled osob", "danger");
            $this->redirect("Payment:detail", array("id" => $id));
        }

        $form = $this['massAddForm'];
        $form['oid']->setDefaultValue($id);

        foreach ($list as $p) {
            $form->addSelect($p['ID'] . '_email', NULL, $p['emails'])
                    ->setPrompt("")
                    ->setDefaultValue(key($p['emails']))
                    ->setAttribute('class', 'form-control');
        }
    }

    public function renderRepayment($id) {
        $campService = $this->context->getService("campService");
        $accountFrom = $this->model->getBankAccount($this->aid);
        $this->template->group = $group = $this->model->getGroup(array_keys($this->readUnits), $id);
        $this['repaymentForm']->setDefaults(array(
            "gid" => $group->id,
            "accountFrom" => $accountFrom,
        ));
        $payments = array();
        foreach ($this->model->getAll($id) as $p) {
            if ($p->state == "completed" && $p->personId != NULL) {
                $payments[$p->personId] = $p;
            }
        }
        $participantsWithRepayment = array_filter($campService->participants->getAll($group->sisId), function ($p) {
            return $p->repayment != NULL;
        });

        $this->template->participants = $participantsWithRepayment;
        $this->template->payments = $payments;
    }

    public function createComponentMassAddForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addHidden("oid");
        $form->addText("defaultAmount", "Částka:")
                ->setAttribute('class', 'form-control input-sm');
        $form->addDatePicker('defaultMaturity', "Splatnost:")//
                ->setAttribute('class', 'form-control input-sm');
        $form->addText("defaultKs", "KS:", NULL, 4);
        $form->addText("defaultNote", "Poznámka:")
                ->setAttribute('class', 'form-control input-sm');
        $form->addSubmit('send', 'Přidat vybrané')
                ->setAttribute("class", "btn btn-primary btn-large");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function massAddFormSubmitted(Form $form) {
        $values = $form->getValues();
        $checkboxs = $form->getHttpData($form::DATA_TEXT, 'ch[]');
        $vals = $form->getHttpData()['vals'];

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro práci s registrací jednotky", "danger");
            $this->redirect("Payment:detail", array("id" => $values->oid));
        }
        //$list = $this->model->getPersons($this->aid, $values->oid);

        if (empty($checkboxs)) {
            $form->addError("Nebyla vybrána žádná osoba k přidání!");
            return;
        }

        foreach ($checkboxs as $pid) {
            $pid = substr($pid, 2);
            $tmpAmount = $vals[$pid]['amount'];
            $tmpMaturity = $vals[$pid]['maturity'];
            $tmpKS = $vals[$pid]['ks'];
            $tmpNote = $vals[$pid]['note'];

            $name = $this->noEmpty($vals[$pid]['name']);
            $amount = $tmpAmount == "" ? $this->noEmpty($values['defaultAmount']) : $tmpAmount;
            if ($amount === NULL) {
                $form->addError("Musí být vyplněna částka."); //[$uid . '_' . $p['ID'] . '_amount']
                return;
            }

            if ($tmpMaturity != "") {
                $maturity = date("Y-m-d", strtotime($tmpMaturity));
            } else {
                if ($values['defaultMaturity'] instanceof \DateTime) {
                    $maturity = date("Y-m-d", strtotime($values['defaultMaturity']));
                } else {
                    $form->addError("Musí být vyplněná splatnost."); //[$uid . '_' . $p['ID'] . '_amount']
                    return;
                }
            }
            $email = $this->noEmpty($vals[$pid]['email']);
            $vs = $this->noEmpty($vals[$pid]['vs']);
            $ks = $tmpKS == "" ? $this->noEmpty($values['defaultKs']) : $tmpKS;
            $note = $tmpNote == "" ? $this->noEmpty($values['defaultNote']) : $tmpNote;

            $this->model->createPayment($values->oid, $name, $email, $amount, $maturity, $pid, $vs, $ks, $note);
        }

        $this->flashMessage("Platby byly přidány");
        $this->redirect("Payment:detail", array("id" => $values->oid));
    }

    public function handleCancel($pid) {
        if (!$this->isEditable) {
            $this->flashMessage("Neplatný požadavek na zrušení platby!", "danger");
            $this->redirect("this");
        }
        if (!$this->model->get(array_keys($this->editUnits), $pid)) {
            $this->flashMessage("Platba pro zrušení nebyla nalezena!", "danger");
            $this->redirect("this");
        }
        if ($this->model->cancelPayment($pid)) {
            $this->flashMessage("Platba byla zrušena.");
        } else {
            $this->flashMessage("Platbu se nepodařilo zrušit!", "danger");
        }
        $this->redirect("this");
    }

    public function handleSend($pid) {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group || !$this->model->get(array_keys($this->editUnits), $pid)) {
            $this->flashMessage("Neplatný požadavek na odeslání emailu!", "danger");
            $this->redirect("this");
        }
        $payment = $this->model->get(array_keys($this->editUnits), $pid);

        if ($this->sendInfoMail($payment, $group)) {
            $this->flashMessage("Informační email byl odeslán.");
        } else {
            $this->flashMessage("Informační email se nepodařilo odeslat!", "danger");
        }
        $this->redirect("this");
    }

    /**
     * rozešle všechny neposlané emaily
     * @param int $gid groupId
     */
    public function handleSendGroup($gid) {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Neoprávněný přístup k záznamu!", "danger");
            $this->redirect("this");
        }
        $payments = $this->model->getAll($gid);
        $cnt = 0;
        $unitIds = array_keys($this->editUnits);
        foreach ($payments as $p) {
            $payment = $this->model->get($unitIds, $p->id);
            $cnt += $this->sendInfoMail($payment, $group);
        }

        if ($cnt > 0) {
            $this->flashMessage("Informační emaily($cnt) byly odeslány.");
        } else {
            $this->flashMessage("Nebyl odeslán žádný informační email!", "danger");
        }
        $this->redirect("this");
    }

    public function handleSendTest($gid) {
        if (!$this->isEditable) {
            $this->flashMessage("Neplatný požadavek na odeslání testovacího emailu!", "danger");
            $this->redirect("this");
        }
        $personalDetail = $this->userService->getPersonalDetail();
        if (!isset($personalDetail->Email)) {
            $this->flashMessage("Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!", "danger");
            $this->redirect("this");
        }
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        $payment = \Nette\Utils\ArrayHash::from(array(
                    "state" => \Model\PaymentTable::PAYMENT_STATE_PREPARING,
                    "name" => "Testovací účel",
                    "email" => $personalDetail->Email,
                    "unitId" => $group->unitId,
                    "amount" => $group->amount != 0 ? $group->amount : rand(50, 1000),
                    "maturity" => $group->maturity instanceof \DateTime ? $group->maturity : new \DateTime(date("Y-m-d", strtotime("+2 week"))),
                    "ks" => $group->ks,
                    "vs" => rand(1000, 100000),
                    "email_info" => $group->email_info,
                    "note" => "obsah poznámky",
                    "groupId" => $gid,
        ));
        if ($this->sendInfoMail($payment, $group)) {
            $this->flashMessage("Testovací email byl odeslán na " . $personalDetail->Email . " .");
        } else {
            $this->flashMessage("Testovací email se nepodařilo odeslat!", "danger");
        }

        $this->redirect("this");
    }

    public function handleComplete($pid) {
        if (!$this->isEditable) {
            $this->flashMessage("Nejste oprávněni k uzavření platby!", "danger");
            $this->redirect("this");
        }
        if ($this->model->completePayment($pid)) {
            $this->flashMessage("Platba byla zaplacena.");
        } else {
            $this->flashMessage("Platbu se nepodařilo uzavřít!", "danger");
        }
        $this->redirect("this");
    }

    public function handlePairPayments($gid) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění párovat platby!", "danger");
            $this->redirect("this");
        }
        try {
            $pairsCnt = $this->bank->pairPayments($this->model, $this->aid, $gid);
        } catch (\Model\BankTimeoutException $exc) {
            $this->template->errMsg[] = "Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.";
        } catch (\Model\BankTimeLimitException $exc) {
            $this->template->errMsg[] = "Mezi dotazy na bankovnictví musí být prodleva 1 minuta!";
        }

        if ($pairsCnt > 0) {
            $this->flashMessage("Podařilo se spárovat platby ($pairsCnt)");
        } else {
            $this->flashMessage("Žádné platby nebyly spárovány");
        }
        $this->redirect("this");
    }

    public function handleGenerateVs($gid) {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Nemáte oprávnění generovat VS!", "danger");
            $this->redirect("Payment:default");
        }
        $maxVS = $this->model->getMaxVS($group['id']);
        if (is_null($maxVS)) {
            $this->flashMessage("Vyplňte VS libovolné platbě a další pak již budou dogenerovány způsobem +1.", "warning");
            $this->redirect("this");
        }
        $payments = $this->model->getAll($gid);
        $cnt = 0;
        foreach ($payments as $payment) {
            if (is_null($payment->vs) && $payment->state == "preparing") {
                $this->model->update($payment->id, array("vs" => ++$maxVS));
                $cnt++;
            }
        }
        $this->flashMessage("Počet dogenerovaných VS: $cnt", "success");
        $this->redirect("this");
    }

    public function handleCloseGroup($gid) {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Nejste oprávněni úpravám akce!", "danger");
            $this->redirect("this");
        }
        $userData = $this->userService->getUserDetail();
        $this->model->updateGroup($gid, array("state" => "closed", "state_info" => "Uživatel " . $userData->Person . " uzavřel skupinu plateb dne " . date("j.n.Y H:i")));
        $this->redirect("this");
    }

    public function handleOpenGroup($gid) {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Nejste oprávněni úpravám akce!", "danger");
            $this->redirect("this");
        }
        $userData = $this->userService->getUserDetail();
        $this->model->updateGroup($gid, array("state" => "open", "state_info" => "Uživatel " . $userData->Person . " otevřel skupinu plateb dne " . date("j.n.Y H:i")), FALSE);
        $this->redirect("this");
    }

    public function createComponentPaymentForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addText("name", "Název/účel")
                ->setAttribute('class', 'form-control')
                ->addRule(Form::FILLED, "Musíte zadat název platby");
        $form->addText("amount", "Částka")
                ->setAttribute('class', 'form-control')
                ->addRule(Form::FILLED, "Musíte vyplnit částku")
                ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo");
        $form->addText("email", "Email")
                ->setAttribute('class', 'form-control')
                ->addCondition(Form::FILLED)
                ->addRule(Form::EMAIL, "Zadaný email nemá platný formát");
        $form->addDatePicker("maturity", "Splatnost")
                ->setAttribute('class', 'form-control');
        $form->addText("vs", "VS", NULL, 10)
                ->setAttribute('class', 'form-control')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, "Variabilní symbol musí být číslo");
        $form->addText("ks", "KS", NULL, 4)
                ->setAttribute('class', 'form-control')
                ->addCondition(Form::FILLED)->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addText("note", "Poznámka")
                ->setAttribute('class', 'form-control');
        $form->addHidden("oid");
        $form->addHidden("pid");
        $form->addSubmit('send', 'Přidat platbu')->setAttribute("class", "btn btn-primary");
        $form->onSubmit[] = array($this, 'paymentSubmitted');
        return $form;
    }

    function paymentSubmitted(Form $form) {
        if (!$this->isEditable) {
            $this->flashMessage("Nejste oprávněni k úpravám plateb!", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();
        if ($v->maturity == NULL) {
            $form['maturity']->addError("Musíte vyplnit splatnost");
            return;
        }
        if ($v->pid != "") {//EDIT
            if ($this->model->update($v->pid, array('state' => 'preparing', 'name' => $v->name, 'email' => $v->email, 'amount' => $v->amount, 'maturity' => $v->maturity, 'vs' => $v->vs, 'ks' => $v->ks, 'note' => $v->note))) {
                $this->flashMessage("Platba byla upravena");
            } else {
                $this->flashMessage("Platbu se nepodařilo založit", "danger");
            }
        } else {//ADD
            if ($this->model->createPayment($v->oid, $v->name, $v->email, $v->amount, $v->maturity, NULL, $v->vs, $v->ks, $v->note)) {
                $this->flashMessage("Platba byla přidána");
            } else {
                $this->flashMessage("Platbu se nepodařilo založit", "danger");
            }
        }
        $this->redirect("detail", array("id" => $v->oid));
    }

    protected function sendInfoMail($payment, $group) {
        try {
            return $this->model->sendInfo($this->template, $payment, $group, $this->unitService);
        } catch (\Nette\Mail\SmtpException $ex) {
            $this->flashMessage("Nepodařilo se připojit k SMTP serveru (" . $ex->getMessage() . ")", "danger");
            $this->flashMessage("V případě problémů s odesláním emailu přes gmail si nastavte možnost použití adresy méně bezpečným aplikacím viz https://support.google.com/accounts/answer/6010255?hl=cs", "warning");
            $this->redirect("this");
        }
    }

    public function createComponentRepaymentForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addHidden("gid");
        $form->addText("accountFrom", "Z účtu:")
                ->addRule(Form::FILLED, "Zadejte číslo účtu ze kterého se mají peníze poslat");
        $form->addDatePicker("date", "Datum splatnosti:")
                ->setDefaultValue(date("j. n. Y", strtotime("+1 Weekday")));
        $form->addSubmit('send', 'Odeslat platby do banky')
                ->setAttribute("class", "btn btn-primary btn-large");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function repaymentFormSubmitted(Form $form) {
        $values = $form->getValues();
        $checkboxs = $form->getHttpData($form::DATA_TEXT, 'ch[]');
        $vals = $form->getHttpData()['vals'];
        $accountFrom = $values->accountFrom;

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro práci s platbami jednotky", "danger");
            $this->redirect("Payment:default", array("id" => $values->gid));
        }
        //$list = $this->model->getPersons($this->aid, $values->oid);

        if (empty($checkboxs)) {
            $form->addError("Nebyl vybrán žádný záznam k vrácení!");
            return;
        }

        $data = array();
        foreach ($checkboxs as $pid) {
            $pid = substr($pid, 2);
            $data[$pid]['name'] = $vals[$pid]['name'];
            $data[$pid]['amount'] = $vals[$pid]['amount'];
            $data[$pid]['account'] = $vals[$pid]['account'];
        }
        $dataToRequest = $this->model->getFioRepaymentString($data, $accountFrom, $date = NULL);
        if (!($bankInfo = $this->bank->getInfo($this->aid))) {
            $this->flashMessage("Není zadán API token z banky!", "danger");
            $this->redirect("this");
        }

        $resultXML = $this->model->sendFioPaymentRequest($dataToRequest, $bankInfo->token);
        $result = (new \SimpleXMLElement($resultXML));

        if ($result->result->errorCode == 0) {//OK
            $this->flashMessage("Vratky byly odeslány do banky");
            $this->redirect("Payment:detail", array("id" => $values->gid));
        } else {
            $form->addError("Chyba z banky: " . $result->ordersDetails->detail->messages->message);
        }
    }

}
