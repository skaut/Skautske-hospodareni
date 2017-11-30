<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupUnitControl;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Factories\IGroupUnitControlFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IPairButtonFactory;
use App\Forms\BaseForm;
use Consistence\Time\TimeFormat;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\Payment;
use Model\Payment\BankAccountService;
use Model\Payment\EmailNotSetException;
use Model\Payment\InvalidBankAccountException;
use Model\Payment\InvalidEmailException;
use Model\Payment\MailCredentialsNotSetException;
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

    protected $readUnits;
    protected $editUnits;
    protected $unitService;

    /** @var int */
    private $id;

    /** @var PaymentService */
    private $model;

    /** @var MailingService */
    private $mailing;

    /** @var BankAccountService */
    private $bankAccounts;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var IPairButtonFactory */
    private $pairButtonFactory;

    /** @var IGroupUnitControlFactory */
    private $unitControlFactory;

    private const NO_MAILER_MESSAGE = 'Nemáte nastavený mail pro odesílání u skupiny';
    private const NO_BANK_ACCOUNT_MESSAGE = 'Vaše jednotka nemá ve Skautisu nastavený bankovní účet';

    public function __construct(
        PaymentService $model,
        UnitService $unitService,
        MailingService $mailing,
        BankAccountService $bankAccounts,
        IMassAddFormFactory $massAddFormFactory,
        IPairButtonFactory $pairButtonFactory,
        IGroupUnitControlFactory $unitControlFactory
    )
    {
        parent::__construct();
        $this->model = $model;
        $this->unitService = $unitService;
        $this->mailing = $mailing;
        $this->bankAccounts = $bankAccounts;
        $this->massAddFormFactory = $massAddFormFactory;
        $this->pairButtonFactory = $pairButtonFactory;
        $this->unitControlFactory = $unitControlFactory;
    }

    protected function startup(): void
    {
        parent::startup();
        //Kontrola ověření přístupu
        $this->readUnits = $this->unitService->getReadUnits($this->user);
        $this->editUnits = $this->unitService->getEditUnits($this->user);
    }

    public function actionDefault(bool $onlyOpen = TRUE): void
    {
        $this->template->onlyOpen = $onlyOpen;
        $groups = $this->model->getGroups(array_keys($this->readUnits), $onlyOpen);

        $groupIds = [];
        $unitIds = [];
        $bankAccountIds = [];
        foreach($groups as $group) {
            $groupIds[] = $group->getId();
            $unitIds[] = $group->getUnitId();
            $bankAccountIds[] = $group->getBankAccountId();
        }

        $bankAccounts = $this->bankAccounts->findByIds(array_filter(array_unique($bankAccountIds)));

        $groupsPairingSupport = [];
        foreach($groups as $group) {
            $accountId = $group->getBankAccountId();
            $groupsPairingSupport[$group->getId()] = $accountId !== NULL && $bankAccounts[$accountId]->getToken() !== NULL;
        }

        $this["pairButton"]->setGroups($groupIds);

        $this->template->groups = $groups;
        $this->template->summarizations = $this->model->getGroupSummaries($groupIds);
        $this->template->groupsPairingSupport = $groupsPairingSupport;
    }

    public function actionDetail(int $id): void
    {
        $group = $this->model->getGroup($id);

        if($group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage("Nemáte oprávnění zobrazit detail plateb", "warning");
            $this->redirect("Payment:default");
        }

        $this->id = $id;
        if ($this->isEditable) {
            $this["pairButton"]->setGroups([$id], $group->getUnitId());
        }

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

        $paymentsForSendEmail = array_filter($payments, function(Payment $p) {
            return $p->getEmail() !== NULL && $p->getState()->equalsValue(State::PREPARING);
        });

        $this->template->isGroupSendActive = $group->getState() === 'open' && !empty($paymentsForSendEmail);
    }

    public function actionEdit(int $pid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění editovat platbu", "warning");
            $this->redirect("Payment:default");
        }

        $payment = $this->model->findPayment($pid);

        if($payment === NULL || $payment->isClosed()) {
            $this->flashMessage('Platba nenalezena', 'warning');
            $this->redirect('Payment:default');
        }

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

        $form = $this['massAddForm'];
        /** @var MassAddForm $form */

        $list = $this->model->getPersons($this->aid, $id);

        foreach ($list as $p) {
            $form->addPerson($p->getId(), $p->getEmails(), $p->getName());
        }

        $this->template->id = $this->id;
        $this->template->showForm = count($list) !== 0;
    }

    public function actionRepayment(int $id): void
    {
        $campService = $this->context->getService("campService");
        $group = $this->model->getGroup($id);

        if($group === NULL || !$this->hasAccessToGroup($group)) {
            $this->flashMessage('K této skupině nemáte přístup');
            $this->redirect('Payment:default');
        }

        $accountFrom = $group->getBankAccountId() !== NULL ?
            $this->bankAccounts->find($group->getBankAccountId())
            : NULL;

        $this['repaymentForm']->setDefaults([
            "gid" => $group->getId(),
            "accountFrom" => $accountFrom,
        ]);

        /** @var Payment[] $payments */
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

            $transaction = $payments[$p->ID_Person]->getTransaction();
            $account = $transaction !== NULL ? $transaction->getBankAccount() : "";
            
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
        } catch (MailCredentialsNotSetException $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (SmtpException $e) {
            $this->smtpError($e);
        } catch (InvalidBankAccountException $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        } catch (InvalidEmailException $e) {
            $this->flashMessage('Platba nemá vyplněný email', 'danger');
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
        } catch (MailCredentialsNotSetException $e) {
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
        } catch (MailCredentialsNotSetException $e) {
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

    protected function createComponentPaymentForm(): Form
    {
        $form = new BaseForm();
        $form->addText("name", "Název/účel")
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, "Musíte zadat název platby");
        $form->addText("amount", "Částka")
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, "Musíte vyplnit částku")
            ->addRule(Form::FLOAT, "Částka musí být zadaná jako číslo")
            ->addRule(Form::MIN, 'Částka musí být větší než 0', 0.01);
        $form->addText("email", "Email")
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, "Zadaný email nemá platný formát");
        $form->addDatePicker("maturity", "Splatnost")
            ->setAttribute('class', 'form-control');
        $form->addVariableSymbol("vs", "VS")
            ->setRequired(FALSE)
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED);
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
        $email = $v->email !== "" ? $v->email : NULL;
        $amount = (float)$v->amount;
        $dueDate = \DateTimeImmutable::createFromMutable($v->maturity);
        $variableSymbol = $v->vs;
        $constantSymbol = $v->ks !== "" ? (int)$v->ks : NULL;
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

    protected function createComponentRepaymentForm(): Form
    {
        $form = new BaseForm();
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

        $bankAccountId = $this->model->getGroup((int) $values->gid)->getBankAccountId();
        $bankAccount = $bankAccountId !== NULL ? $this->bankAccounts->find($bankAccountId) : NULL;

        if ($bankAccount === NULL || $bankAccount->getToken() === NULL) {
            $this->flashMessage("Není zadán API token z banky!", "danger");
            $this->redirect("this");
        }

        $resultXML = $this->model->sendFioPaymentRequest($dataToRequest, $bankAccount->getToken());
        $result = (new \SimpleXMLElement($resultXML));

        if ($result->result->errorCode == 0) {//OK
            $this->flashMessage("Vratky byly odeslány do banky");
            $this->redirect("Payment:detail", ["id" => $values->gid]);
        } else {
            $form->addError("Chyba z banky: " . $result->ordersDetails->detail->messages->message);
        }
    }

    protected function createComponentPairButton(): PairButton
    {
        return $this->pairButtonFactory->create();
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }

    protected function createComponentUnit(): GroupUnitControl
    {
        return $this->unitControlFactory->create($this->id);
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
