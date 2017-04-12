<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Consistence\Time\TimeFormat;
use Model\BankService;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\Payment;
use Model\Mail\MailerNotFoundException;
use Model\Payment\EmailNotSetException;
use Model\Payment\InvalidBankAccountException;
use Model\Payment\MailingService;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\PaymentService;
use Model\UnitService;
use Nette\Application\UI\Form;
use Nette\Mail\SmtpException;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class PaymentPresenter extends BasePresenter
{

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

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    private const NO_MAILER_MESSAGE = 'Nemáte nastavený mail pro odesílání u skupiny';
    private const NO_BANK_ACCOUNT_MESSAGE = 'Vaše jednotka nemá ve Skautisu nastavený bankovní účet';

    public function __construct(
        PaymentService $paymentService,
        BankService $bankService,
        UnitService $unitService,
        MailingService $mailing,
        IMassAddFormFactory $massAddFormFactory
    )
    {
        parent::__construct($paymentService);
        $this->bank = $bankService;
        $this->unitService = $unitService;
        $this->mailing = $mailing;
        $this->massAddFormFactory = $massAddFormFactory;
    }

    protected function startup(): void
    {
        parent::startup();
        //Kontrola ověření přístupu
        $this->readUnits = $this->unitService->getReadUnits($this->user);
        $this->editUnits = $this->unitService->getEditUnits($this->user);
    }

    public function renderDefault(int $onlyOpen = 1): void
    {
        $this->template->onlyOpen = $onlyOpen;
        $groups = $this->model->getGroups(array_keys($this->readUnits), $onlyOpen);

        $groupIds = array_map(function(Group $group) {
            return $group->getId();
        }, $groups);

        $this->template->groups = $groups;
        $this->template->summarizations = $this->model->getGroupSummaries($groupIds);
    }

    public function actionDetail($id): void
    {
        $this->id = $id;
        $this->bankInfo = $this->bank->getInfo($this->aid);
    }

    public function renderDetail(int $id): void
    {
        $group = $this->model->getGroup($id);

        if($group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage("Nemáte oprávnění zobrazit detail plateb", "warning");
            $this->redirect("Payment:default");
        }

        $this->template->units = $this->readUnits;
        $this->template->group = $group;

        $this->template->nextVS = $nextVS = $this->model->getNextVS($group->getId());
        $form = $this['paymentForm'];
        $form->setDefaults([
            'amount' => $group->getDefaultAmount(),
            'maturity' => $group->getDueDate() !== NULL ? $group->getDueDate()->format('d.m.Y') : NULL,
            'ks' => $group->getConstantSymbol(),
            'oid' => $group->getId(),
            'vs' => $nextVS !== NULL ? (string)$nextVS : "",
        ]);

        $this->template->payments = $payments = $this->model->findByGroup($id);
        $this->template->summarize = $this->model->getGroupSummaries([$id])[$id];
        $this->template->now = new \DateTimeImmutable();
        $paymentsForSendEmail = array_filter($payments, function($p) {
            return strlen($p->email) > 4 && $p->state == "preparing";
        });
        $this->template->isGroupSendActive = ($group->getState() === 'open') && count($paymentsForSendEmail) > 0;
        $this->template->canPair = isset($this->bankInfo->token);
    }

    public function renderEdit(int $pid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění editovat platbu", "warning");
            $this->redirect("Payment:default");
        }

        $payment = $this->model->findPayment($pid);
        $form = $this['paymentForm'];
        $form['send']->caption = "Upravit";
        $form->setDefaults([
            'name' => $payment->getName(),
            'email' => $payment->getEmail(),
            'amount' => $payment->getAmount(),
            'maturity' => TimeFormat::createDateTimeFromDateTimeInterface($payment->getDueDate()),
            'vs' => $payment->getVariableSymbol(),
            'ks' => $payment->getConstantSymbol(),
            'note' => $payment->getNote(),
            'oid' => $payment->getGroupId(),
            'pid' => $pid,
        ]);

        $this->template->linkBack = $this->link("detail", ["id" => $payment->getGroupId()]);
    }

    public function actionMassAdd(int $id): void
    {
        //ověření přístupu
        $this->template->unitPairs = $this->readUnits;

        $group = $this->model->getGroup($id);

        $this->id = $id;

        if($group === NULL || !$this->hasAccessToGroup($group) || !$this->isEditable) {
            $this->flashMessage("Neplatný požadavek na přehled osob", "danger");
            $this->redirect("Payment:detail", ["id" => $id]); // redirect elsewhere?
        }

        $list = $this->model->getPersons($this->aid, $id); //@todo:?nahradit aid za array_keys($this->editUnits) ??

        $form = $this['massAddForm'];
        /* @var $form MassAddForm */

        foreach ($list as $p) {
            $form->addPerson($p["ID"], $p["emails"], $p["DisplayName"]);
        }

        $this->template->id = $this->id;
        $this->template->showForm = !empty($list);
    }

    public function actionRepayment(int $id): void
    {
        $campService = $this->context->getService("campService");
        $accountFrom = $this->model->getBankAccount($this->aid);

        $group = $this->model->getGroup($id);

        if($group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage('K této skupině nemáte přístup');
            $this->redirect('Payment:default');
        }

        $this['repaymentForm']->setDefaults([
            "gid" => $group->getId(),
            "accountFrom" => $accountFrom,
        ]);

        /* @var $payments Payment[] */
        $payments = [];
        foreach ($this->model->findByGroup($id) as $p) {
            if ($p->getState()->equalsValue(State::COMPLETED) && $p->getPersonId() !== NULL) {
                $payments[$p->getPersonId()] = $p;
            }
        }
        $participantsWithRepayment = array_filter($campService->participants->getAll($group->getSkautisId()), function ($p) {
            return $p->repayment != NULL;
        });

        $form = $this['repaymentForm'];
        foreach ($participantsWithRepayment as $p) {
            $pid = "p_" . $p->ID;
            $form->addCheckbox($pid);
            $form->addText($pid . "_name")
                ->setDefaultValue("Vratka - " . $p->Person . " - " . $group->getName())
                ->addConditionOn($form[$pid], Form::EQUAL, TRUE)
                ->setRequired("Zadejte název vratky!");
            $form->addText($pid . "_amount")
                ->setDefaultValue($p->repayment)
                ->addConditionOn($form[$pid], Form::EQUAL, TRUE)
                ->setRequired("Zadejte částku vratky u " . $p->Person)
                ->addRule(Form::NUMERIC, "Vratka musí být číslo!");
            $account = $payments[$p->ID_Person]->getTransaction()->getBankAccount() ?? "";
            $form->addText($pid . "_account")
                ->setDefaultValue($account)
                ->addConditionOn($form[$pid], Form::EQUAL, TRUE)
                ->addRule(Form::PATTERN, "Zadejte platný bankovní účet u " . $p->Person, "[0-9]{5,}/[0-9]{4}$");
        }

        $this->template->participants = $participantsWithRepayment;
        $this->template->payments = $payments;
    }

    public function handleCancel(int $pid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Neplatný požadavek na zrušení platby!", "danger");
            $this->redirect("this");
        }

        try {
            $this->model->cancelPayment($pid);
        } catch (PaymentNotFoundException $e) {
            $this->flashMessage("Platba nenalezena!", "danger");
        } catch(\Model\Payment\PaymentClosedException $e) {
            $this->flashMessage("Tato platba už je uzavřená", "danger");
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
            if ($sentCount > 0) {
                $this->flashMessage("Informační emaily($sentCount) byly odeslány.");
            } else {
                $this->flashMessage('Nebyl odeslán žádný informační email!', 'danger');
            }
        } catch (MailerNotFoundException $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (SmtpException $e) {
            $this->smtpError($e);
        } catch (InvalidBankAccountException $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        }

        $this->redirect('this');
    }

    public function handleSendTest($gid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Neplatný požadavek na odeslání testovacího emailu!", "danger");
            $this->redirect("this");
        }

        try {
            $email = $this->mailing->sendTestMail($gid, $this->user->getId());
            $this->flashMessage("Testovací email byl odeslán na $email.");
        } catch (MailerNotFoundException $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (SmtpException $e) {
            $this->smtpError($e);
        } catch (InvalidBankAccountException $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        } catch(EmailNotSetException $e) {
            $this->flashMessage("Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!", "danger");
        }

        $this->redirect("this");
    }

    public function handleComplete(int $pid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nejste oprávněni k uzavření platby!", "danger");
            $this->redirect("this");
        }

        try {
            $this->model->completePayment($pid);
            $this->flashMessage("Platba byla zaplacena.");
        } catch(\Model\Payment\PaymentClosedException $e) {
            $this->flashMessage("Tato platba už je uzavřená", "danger");
        }

        $this->redirect("this");
    }

    public function handlePairPayments($gid): void
    {
        $this->pairPairments($gid);
    }

    public function handleGenerateVs(int $gid): void
    {
        $group = $this->model->getGroup($gid);

        if(!$this->isEditable || $group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage("Nemáte oprávnění generovat VS!", "danger");
            $this->redirect("Payment:default");
        }

        $nextVS = $this->model->getNextVS($group->getId());

        if ($nextVS === NULL) {
            $this->flashMessage("Vyplňte VS libovolné platbě a další pak již budou dogenerovány způsobem +1.", "warning");
            $this->redirect("this");
        }

        $numberOfUpdatedVS = $this->model->generateVs($gid);
        $this->flashMessage("Počet dogenerovaných VS: $numberOfUpdatedVS ", "success");
        $this->redirect("this");
    }

    public function handleCloseGroup(int $gid): void
    {
        $group = $this->model->getGroup($gid);
        if (!$this->isEditable || $group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage("Nejste oprávněni úpravám akce!", "danger");
            $this->redirect("this");
        }

        $userData = $this->userService->getUserDetail();
        $note = "Uživatel " . $userData->Person . " uzavřel skupinu plateb dne " . date("j.n.Y H:i");

        try {
            $this->model->closeGroup($gid, $note);
        } catch(\Model\Payment\GroupNotFoundException $e) {
        }

        $this->redirect("this");
    }

    public function handleOpenGroup(int $gid): void
    {
        $group = $this->model->getGroup($gid);

        if(!$this->isEditable || $group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage("Nejste oprávněni úpravám akce!", "danger");
            $this->redirect("this");
        }

        $userData = $this->userService->getUserDetail();
        $note = "Uživatel " . $userData->Person . " otevřel skupinu plateb dne " . date("j.n.Y H:i");

        try {
            $this->model->openGroup($gid, $note);
        } catch(\Model\Payment\GroupNotFoundException $e) {
        }

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

        $id = $v->pid != "" ? (int)$v->pid : NULL;
        $name = $v->name;
        $email = $v->email;
        $amount = (float)$v->amount;
        $dueDate = \DateTimeImmutable::createFromMutable($v->maturity);
        $variableSymbol = $v->vs !== "" ? (int)$v->vs : NULL;
        $constantSymbol = $v->ks !=="" ? (int)$v->ks : NULL;
        $note = (string)$v->note;

        if ($id !== NULL) {//EDIT
            $this->model->update($id, $name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
            $this->flashMessage("Platba byla upravena");
        } else {//ADD
            $this->model->createPayment(
                (int)$v->oid,
                $name,
                $email,
                $amount,
                $dueDate,
                NULL,
                $variableSymbol,
                $constantSymbol,
                $note
            );
            $this->flashMessage("Platba byla přidána");
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
            ->setRequired('Musíte vyplnit počet dní')
            ->addRule($form::MIN, 'Musíte zadat alespoň počet dní z nastavení: %d', $days)
            ->setType('number');
        $form->addSubmit('pair', 'Párovat')->setAttribute('class', 'ajax');

        $form->onSuccess[] = function ($form, $values): void {
            $this->pairPairments($this->id, $values->days);
        };
        $this->redrawControl('pairForm');
        return $form;
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
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
            $pairsCnt = $this->bank->pairPayments($this->aid, $groupId, $days);
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

    private function hasAccessToGroup(Group $group): bool
    {
        return in_array($group->getUnitId(), array_keys($this->readUnits), TRUE);
    }

}
