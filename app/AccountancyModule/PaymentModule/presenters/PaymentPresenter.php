<?php

namespace App\AccountancyModule\PaymentModule;

use Model\BankService;
use Model\Mail\MailerNotFoundException;
use Model\Payment\InvalidBankAccountException;
use Model\Payment\MailingService;
use Model\PaymentService;
use Model\UnitService;
use Nette\Application\UI\Form;
use Nette\Mail\SmtpException;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentPresenter extends BasePresenter
{

    protected $notFinalStates;

    /**
     *
     * @var BankService
     */
    protected $bank;
    protected $readUnits;
    protected $editUnits;
    protected $unitService;

    /** @var int */
    private $id;

    /** @var object */
    private $bankInfo;

    /** @var MailingService */
    private $mailing;

    private const NO_MAILER_MESSAGE = 'Nemáte nastavený mail pro odesílání u skupiny';
    private const NO_BANK_ACCOUNT_MESSAGE = 'Vaše jednotka nemá ve Skautisu nastavený bankovní účet';

    public function __construct(
        PaymentService $paymentService,
        BankService $bankService,
        UnitService $unitService,
        MailingService $mailing
    )
    {
        parent::__construct($paymentService);
        $this->bank = $bankService;
        $this->unitService = $unitService;
        $this->mailing = $mailing;
    }

    protected function startup(): void
    {
        parent::startup();
        //Kontrola ověření přístupu
        $this->template->notFinalStates = $this->notFinalStates = $this->model->getNonFinalStates();
        //$this->groups = $this->model->getGroupsIn($this->user->getIdentity()->access['read']);
        $this->readUnits = $this->unitService->getReadUnits($this->user);
        $this->editUnits = $this->unitService->getEditUnits($this->user);
    }

    public function renderDefault(int $onlyOpen = 1): void
    {
        $this->template->onlyOpen = $onlyOpen;
        $groups = $this->model->getGroups(array_keys($this->readUnits), $onlyOpen);
        foreach ($groups as $gid => $g) {
            $groups[$gid]['sumarize'] = $this->model->summarizeByState($gid);
        }
        $this->template->groups = $groups;
        $this->template->payments = $this->model->getAll(array_keys($groups), TRUE);
    }

    public function actionDetail($id): void
    {
        $this->id = $id;
        $this->bankInfo = $this->bank->getInfo($this->aid);
    }

    public function renderDetail($id): void
    {
        $this->template->units = $this->readUnits;
        $this->template->group = $group = $this->model->getGroup(array_keys($this->readUnits), $id);
        if (!$group) {
            $this->flashMessage("Nemáte oprávnění zobrazit detail plateb", "warning");
            $this->redirect("Payment:default");
        }
        $this->template->nextVS = $nextVS = $this->model->getNextVS($group['id']);
        $form = $this['paymentForm'];
        $form->setDefaults([
            'amount' => $group['amount'],
            'maturity' => $group['maturity'],
            'ks' => $group['ks'],
            'oid' => $group['id'],
            'vs' => $nextVS != NULL ? $nextVS : "",
        ]);

        $this->template->payments = $payments = $this->model->getAll($id);
        $this->template->summarize = $this->model->summarizeByState($id);
        $paymentsForSendEmail = array_filter($payments, create_function('$p', 'return strlen($p->email)>4 && $p->state == "preparing";'));
        $this->template->isGroupSendActive = ($group->state == 'open') && count($paymentsForSendEmail) > 0;
        $this->template->canPair = isset($this->bankInfo->token);
    }

    public function renderEdit($pid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění editovat platbu", "warning");
            $this->redirect("Payment:default");
        }
        $payment = $this->model->get(array_keys($this->editUnits), $pid);
        $form = $this['paymentForm'];
        $form['send']->caption = "Upravit";
        $form->setDefaults([
            'name' => $payment->name,
            'email' => $payment->email,
            'amount' => $payment->amount,
            'maturity' => $payment->maturity,
            'vs' => $payment->vs,
            'ks' => $payment->ks,
            'note' => $payment->note,
            'oid' => $payment->groupId,
            'pid' => $payment->id,
        ]);

        $this->template->linkBack = $this->link("detail", ["id" => $payment->groupId]);
    }

    public function actionMassAdd($id): void
    {
        //ověření přístupu
        $this->template->unitPairs = $this->readUnits;
        $this->template->detail = $detail = $this->model->getGroup(array_keys($this->readUnits), $id);
        $this->template->list = $list = $this->model->getPersons($this->aid, $id); //@todo:?nahradit aid za array_keys($this->editUnits) ??

        if (!$detail) {
            $this->flashMessage("Neplatný požadavek na přehled osob", "danger");
            $this->redirect("Payment:detail", ["id" => $id]);
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

    public function actionRepayment($id): void
    {
        $campService = $this->context->getService("campService");
        $accountFrom = $this->model->getBankAccount($this->aid);
        $this->template->group = $group = $this->model->getGroup(array_keys($this->readUnits), $id);
        $this['repaymentForm']->setDefaults([
            "gid" => $group->id,
            "accountFrom" => $accountFrom,
        ]);
        $payments = [];
        foreach ($this->model->getAll($id) as $p) {
            if ($p->state == "completed" && $p->personId != NULL) {
                $payments[$p->personId] = $p;
            }
        }
        $participantsWithRepayment = array_filter($campService->participants->getAll($group->sisId), function ($p) {
            return $p->repayment != NULL;
        });

        $form = $this['repaymentForm'];
        foreach ($participantsWithRepayment as $p) {
            $pid = "p_" . $p->ID;
            $form->addCheckbox($pid);
            $form->addText($pid . "_name")
                ->setDefaultValue("Vratka - " . $p->Person . " - " . $group->label)
                ->addConditionOn($form[$pid], Form::EQUAL, TRUE)
                ->setRequired("Zadejte název vratky!");
            $form->addText($pid . "_amount")
                ->setDefaultValue($p->repayment)
                ->addConditionOn($form[$pid], Form::EQUAL, TRUE)
                ->setRequired("Zadejte částku vratky u " . $p->Person)
                ->addRule(Form::NUMERIC, "Vratka musí být číslo!");
            $account = isset($payments[$p->ID_Person]->paidFrom) ? $payments[$p->ID_Person]->paidFrom : "";
            $form->addText($pid . "_account")
                ->setDefaultValue($account)
                ->addConditionOn($form[$pid], Form::EQUAL, TRUE)
                ->addRule(Form::PATTERN, "Zadejte platný bankovní účet u " . $p->Person, "[0-9]{5,}/[0-9]{4}$");
        }

        $this->template->participants = $participantsWithRepayment;
        $this->template->payments = $payments;
    }

    public function createComponentMassAddForm($name): Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addHidden("oid");
        $form->addText("defaultAmount", "Částka:")
            ->setAttribute('class', 'form-control input-sm');
        $form->addDatePicker('defaultMaturity', "Splatnost:")//
        ->setAttribute('class', 'form-control input-sm');
        $form->addText("defaultKs", "KS:")
            ->setMaxLength(4);
        $form->addText("defaultNote", "Poznámka:")
            ->setAttribute('class', 'form-control input-sm');
        $form->addSubmit('send', 'Přidat vybrané')
            ->setAttribute("class", "btn btn-primary btn-large");

        $form->onSubmit[] = function (Form $form): void {
            $this->massAddFormSubmitted($form);
        };

        return $form;
    }

    private function massAddFormSubmitted(Form $form): void
    {
        $values = $form->getValues();
        $checkboxs = $form->getHttpData($form::DATA_TEXT, 'ch[]');
        $vals = $form->getHttpData()['vals'];

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro práci s registrací jednotky", "danger");
            $this->redirect("Payment:detail", ["id" => $values->oid]);
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
        $this->redirect("Payment:detail", ["id" => $values->oid]);
    }

    public function handleCancel($pid): void
    {
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

    private function checkEditation(): void
    {
        if (!$this->isEditable || !isset($this->readUnits[$this->aid])) {
            $this->flashMessage('Nemáte oprávnění pracovat s touto skupinou!', 'danger');
            $this->redirect('this');
        }
    }

    public function handleSend($pid): void
    {
        $this->checkEditation();

        try {
            $this->mailing->sendEmail($pid, $this->user->getId());
            $this->flashMessage('Informační email byl odeslán.');
        } catch (MailerNotFoundException $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (SmtpException $e) {
            $this->smtpError($e);
        } catch (InvalidBankAccountException $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        }

        $this->redirect('this');
    }

    /**
     * rozešle všechny neposlané emaily
     * @param int $gid groupId
     */
    public function handleSendGroup($gid): void
    {
        $this->checkEditation();

        try {
            $sentCount = $this->mailing->sendEmailForGroup($gid, $this->user->getId());
        } catch (MailerNotFoundException $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (SmtpException $e) {
            $this->smtpError($e);
            $this->redirect('this');
        } catch (InvalidBankAccountException $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        }

        if ($sentCount > 0) {
            $this->flashMessage("Informační emaily($sentCount) byly odeslány.");
        } else {
            $this->flashMessage('Nebyl odeslán žádný informační email!', 'danger');
        }
        $this->redirect('this');
    }

    public function handleSendTest($gid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Neplatný požadavek na odeslání testovacího emailu!", "danger");
            $this->redirect("this");
        }
        $personalDetail = $this->userService->getPersonalDetail();
        if (!isset($personalDetail->Email)) {
            $this->flashMessage("Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!", "danger");
            $this->redirect("this");
        }

        $email = $personalDetail->Email;

        try {
            $this->mailing->sendTestMail($gid, $email, $this->user->getId());
            $this->flashMessage("Testovací email byl odeslán na $email.");
        } catch (MailerNotFoundException $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (SmtpException $e) {
            $this->smtpError($e);
        } catch (InvalidBankAccountException $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        }

        $this->redirect("this");
    }

    public function handleComplete($pid): void
    {
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

    public function handlePairPayments($gid): void
    {
        $this->pairPairments($gid);
    }

    public function handleGenerateVs($gid): void
    {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Nemáte oprávnění generovat VS!", "danger");
            $this->redirect("Payment:default");
        }
        $nextVS = $this->model->getNextVS($group['id']);
        if (is_null($nextVS)) {
            $this->flashMessage("Vyplňte VS libovolné platbě a další pak již budou dogenerovány způsobem +1.", "warning");
            $this->redirect("this");
        }

        $numberOfUpdatedVS = $this->model->generateVs($gid);
        $this->flashMessage("Počet dogenerovaných VS: $numberOfUpdatedVS ", "success");
        $this->redirect("this");
    }

    public function handleCloseGroup($gid): void
    {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Nejste oprávněni úpravám akce!", "danger");
            $this->redirect("this");
        }
        $userData = $this->userService->getUserDetail();
        $this->model->updateGroup($gid, ["state" => "closed", "state_info" => "Uživatel " . $userData->Person . " uzavřel skupinu plateb dne " . date("j.n.Y H:i")]);
        $this->redirect("this");
    }

    public function handleOpenGroup($gid): void
    {
        $group = $this->model->getGroup(array_keys($this->readUnits), $gid);
        if (!$this->isEditable || !$group) {
            $this->flashMessage("Nejste oprávněni úpravám akce!", "danger");
            $this->redirect("this");
        }
        $userData = $this->userService->getUserDetail();
        $this->model->updateGroup($gid, ["state" => "open", "state_info" => "Uživatel " . $userData->Person . " otevřel skupinu plateb dne " . date("j.n.Y H:i")], FALSE);
        $this->redirect("this");
    }

    public function createComponentPaymentForm($name): Form
    {
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
        $form->addText("vs", "VS")
            ->setMaxLength(10)
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, "Variabilní symbol musí být číslo");
        $form->addText("ks", "KS")
            ->setMaxLength(4)
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED)->addRule(Form::INTEGER, "Konstantní symbol musí být číslo");
        $form->addText("note", "Poznámka")
            ->setAttribute('class', 'form-control');
        $form->addHidden("oid");
        $form->addHidden("pid");
        $form->addSubmit('send', 'Přidat platbu')->setAttribute("class", "btn btn-primary");

        $form->onSubmit[] = function (Form $form): void {
            $this->paymentSubmitted($form);
        };

        return $form;
    }

    private function paymentSubmitted(Form $form): void
    {
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
            if ($this->model->update($v->pid, ['state' => 'preparing', 'name' => $v->name, 'email' => $v->email, 'amount' => $v->amount, 'maturity' => $v->maturity, 'vs' => $v->vs, 'ks' => $v->ks, 'note' => $v->note])) {
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
        $this->redirect("detail", ["id" => $v->oid]);
    }

    protected function createComponentRepaymentForm($name): Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addHidden("gid");
        $form->addText("accountFrom", "Z účtu:")
            ->addRule(Form::FILLED, "Zadejte číslo účtu ze kterého se mají peníze poslat");
        $form->addDatePicker("date", "Datum splatnosti:")
            ->setDefaultValue(date("j. n. Y", strtotime("+1 Weekday")));
        $form->addSubmit('send', 'Odeslat platby do banky')
            ->setAttribute("class", "btn btn-primary btn-large");

        $form->onSubmit[] = function (Form $form): void {
            $this->repaymentFormSubmitted($form);
        };

        return $form;
    }

    private function repaymentFormSubmitted(Form $form): void
    {
        $values = $form->getValues();

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro práci s platbami jednotky", "danger");
            $this->redirect("Payment:default", ["id" => $values->gid]);
        }
        //$list = $this->model->getPersons($this->aid, $values->oid);

        $accountFrom = $values->accountFrom;
        $ids = array_keys(array_filter((array)$values, function ($val) {
            return (is_bool($val) && $val);
        }));

        if (empty($ids)) {
            $form->addError("Nebyl vybrán žádný záznam k vrácení!");
            return;
        }

        $bankValidator = new \BankAccountValidator\Czech();
        $data = [];
        foreach ($ids as $pid) {
            $pid = substr($pid, 2);
            $data[$pid]['name'] = $values["p_" . $pid . "_name"];
            $data[$pid]['amount'] = $values["p_" . $pid . "_amount"];
            $data[$pid]['account'] = $values["p_" . $pid . "_account"];
            if (!($bankValidator->validate($data[$pid]['account']))) {
                $form->addError("Neplatné číslo účtu: '" . $data[$pid]['account'] . "' u jména '" . $data[$pid]['name'] . "' !");
                return;
            }
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
            $this->redirect("Payment:detail", ["id" => $values->gid]);
        } else {
            $form->addError("Chyba z banky: " . $result->ordersDetails->detail->messages->message);
        }
    }

    protected function createComponentPairForm(): Form
    {
        $form = $this->formFactory->create(TRUE);

        $days = $this->bankInfo->daysback ?? 0;

        $form->addText('days', 'Počet dní', 2, 2)
            ->setDefaultValue($days)
            ->addRule($form::MIN, 'Musíte zadat alespoň počet dní z nastavení: %d', $days)
            ->setType('number');
        $form->addSubmit('pair', 'Párovat')->setAttribute('class', 'ajax');

        $form->onSuccess[] = function ($form, $values): void {
            $this->pairPairments($this->id, $values->days);
        };
        $this->redrawControl('pairForm');
        return $form;
    }

    /**
     * @param int $groupId
     * @param int|NULL $days
     */
    private function pairPairments($groupId, $days = NULL): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění párovat platby!", "danger");
            $this->redraw('pairForm');
        }
        try {
            $pairsCnt = $this->bank->pairPayments($this->model, $this->aid, $groupId, $days);
        } catch (\Model\BankTimeoutException $exc) {
            $this->flashMessage("Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.", 'danger');
        } catch (\Model\BankTimeLimitException $exc) {
            $this->flashMessage("Mezi dotazy na bankovnictví musí být prodleva 1 minuta!", 'danger');
        }

        if (isset($pairsCnt) && $pairsCnt > 0) {
            $this->flashMessage("Podařilo se spárovat platby ($pairsCnt)");
        } else {
            $this->flashMessage("Žádné platby nebyly spárovány");
        }
        $this->redraw('pairForm');
    }

    /**
     * @param string $snippet
     */
    private function redraw($snippet): void
    {
        if ($this->isAjax()) {
            $this->redrawControl($snippet);
        } else {
            $this->redirect('this');
        }
    }

    private function smtpError(SmtpException $e): void
    {
        $this->flashMessage("Nepodařilo se připojit k SMTP serveru ({$e->getMessage()})", 'danger');
        $this->flashMessage('V případě problémů s odesláním emailu přes gmail si nastavte možnost použití adresy méně bezpečným aplikacím viz https://support.google.com/accounts/answer/6010255?hl=cs', 'warning');
    }

}
