<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupUnitControl;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Components\RemoveGroupDialog;
use App\AccountancyModule\PaymentModule\Factories\IGroupUnitControlFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IPairButtonFactory;
use App\AccountancyModule\PaymentModule\Factories\IRemoveGroupDialogFactory;
use App\Forms\BaseForm;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\Payment;
use Model\Payment\BankAccountService;
use Model\Payment\Commands\Mailing\SendPaymentInfo;
use Model\Payment\EmailNotSet;
use Model\Payment\GroupNotFound;
use Model\Payment\InvalidBankAccount;
use Model\Payment\InvalidSmtp;
use Model\Payment\MailCredentialsNotSet;
use Model\Payment\MailingService;
use Model\Payment\Payment\State;
use Model\Payment\PaymentClosed;
use Model\Payment\PaymentNotFound;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\PaymentService;
use Model\UnitService;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_unique;
use function count;
use function date;
use function sprintf;

class PaymentPresenter extends BasePresenter
{
    /** @var string[] */
    protected $readUnits;

    /** @var UnitService */
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

    /** @var IRemoveGroupDialogFactory */
    private $removeGroupDialogFactory;

    private const NO_MAILER_MESSAGE       = 'Nemáte nastavený mail pro odesílání u skupiny';
    private const NO_BANK_ACCOUNT_MESSAGE = 'Vaše jednotka nemá ve Skautisu nastavený bankovní účet';

    public function __construct(
        PaymentService $model,
        UnitService $unitService,
        MailingService $mailing,
        BankAccountService $bankAccounts,
        IMassAddFormFactory $massAddFormFactory,
        IPairButtonFactory $pairButtonFactory,
        IGroupUnitControlFactory $unitControlFactory,
        IRemoveGroupDialogFactory $removeGroupDialogFactory
    ) {
        parent::__construct();
        $this->model                    = $model;
        $this->unitService              = $unitService;
        $this->mailing                  = $mailing;
        $this->bankAccounts             = $bankAccounts;
        $this->massAddFormFactory       = $massAddFormFactory;
        $this->pairButtonFactory        = $pairButtonFactory;
        $this->unitControlFactory       = $unitControlFactory;
        $this->removeGroupDialogFactory = $removeGroupDialogFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        //Kontrola ověření přístupu
        $this->readUnits = $this->unitService->getReadUnits($this->user);
    }

    public function actionDefault(bool $onlyOpen = true) : void
    {
        $groups = $this->model->getGroups(array_keys($this->readUnits), $onlyOpen);

        $groupIds       = [];
        $bankAccountIds = [];
        foreach ($groups as $group) {
            $groupIds[]       = $group->getId();
            $bankAccountIds[] = $group->getBankAccountId();
        }

        $bankAccounts = $this->bankAccounts->findByIds(array_filter(array_unique($bankAccountIds)));

        $groupsPairingSupport = [];
        foreach ($groups as $group) {
            $accountId                             = $group->getBankAccountId();
            $groupsPairingSupport[$group->getId()] = $accountId !== null && $bankAccounts[$accountId]->getToken() !== null;
        }

        $this['pairButton']->setGroups($groupIds);

        $this->template->setParameters([
            'onlyOpen' => $onlyOpen,
            'groups'               => $groups,
            'summarizations'       => $this->model->getGroupSummaries($groupIds),
            'groupsPairingSupport' => $groupsPairingSupport,
        ]);
    }

    public function actionDetail(int $id) : void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->hasAccessToGroup($group)) {
            $this->flashMessage('Nemáte oprávnění zobrazit detail plateb', 'warning');
            $this->redirect('Payment:default');
        }

        $this->id = $id;
        if ($this->isEditable) {
            $this['pairButton']->setGroups([$id]);
        }

        $nextVS = $this->model->getNextVS($group->getId());
        $form   = $this['paymentForm'];
        $form->setDefaults(
            [
            'amount' => $group->getDefaultAmount(),
            'maturity' => $group->getDueDate(),
            'ks' => $group->getConstantSymbol(),
            'oid' => $group->getId(),
            'vs' => $nextVS !== null ? (string) $nextVS : '',
            ]
        );

        $payments = $this->getPaymentsForGroup($id);

        $paymentsForSendEmail = array_filter(
            $payments,
            function (Payment $p) {
                return $p->getEmail() !== null && $p->getState()->equalsValue(State::PREPARING);
            }
        );

        $this->template->setParameters([
            'group' => $group,
            'nextVS' => $nextVS,
            'payments'  => $payments,
            'summarize' => $this->model->getGroupSummaries([$id])[$id],
            'now'       => new \DateTimeImmutable(),
            'isGroupSendActive' => $group->getState() === 'open' && ! empty($paymentsForSendEmail),
        ]);
    }

    public function actionEdit(int $pid) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění editovat platbu', 'warning');
            $this->redirect('Payment:default');
        }

        $payment = $this->model->findPayment($pid);

        if ($payment === null || $payment->isClosed()) {
            $this->flashMessage('Platba nenalezena', 'warning');
            $this->redirect('Payment:default');
        }

        $form                  = $this['paymentForm'];
        $form['send']->caption = 'Upravit';
        $form->setDefaults(
            [
            'name' => $payment->getName(),
            'email' => $payment->getEmail(),
            'amount' => $payment->getAmount(),
            'maturity' => $payment->getDueDate(),
            'vs' => $payment->getVariableSymbol(),
            'ks' => $payment->getConstantSymbol(),
            'note' => $payment->getNote(),
            'oid' => $payment->getGroupId(),
            'pid' => $pid,
            ]
        );

        $this->template->setParameters(['group' => $this->model->getGroup($payment->getGroupId())]);
    }

    /**
     * @param null $aid - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $aid = null) : void
    {
        //ověření přístupu

        $group = $this->model->getGroup($id);

        $this->id = $id;

        if ($group === null || ! $this->hasAccessToGroup($group) || ! $this->isEditable) {
            $this->flashMessage('Neplatný požadavek na přehled osob', 'danger');
            $this->redirect('Payment:detail', ['id' => $id]); // redirect elsewhere?
        }

        /** @var MassAddForm $form */
        $form = $this['massAddForm'];
        $list = $this->model->getPersons($this->aid, $id);

        foreach ($list as $p) {
            $form->addPerson($p->getId(), $p->getEmails(), $p->getName());
        }

        $this->template->setParameters([
            'unitPairs' => $this->readUnits,
            'group'    => $group,
            'id'        => $this->id,
            'showForm'  => count($list) !== 0,
        ]);
    }

    public function handleCancel(int $pid) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Neplatný požadavek na zrušení platby!', 'danger');
            $this->redirect('this');
        }

        try {
            $this->model->cancelPayment($pid);
        } catch (PaymentNotFound $e) {
            $this->flashMessage('Platba nenalezena!', 'danger');
        } catch (PaymentClosed $e) {
            $this->flashMessage('Tato platba už je uzavřená', 'danger');
        }
        $this->redirect('this');
    }

    private function checkEditation() : void
    {
        if ($this->isEditable && isset($this->readUnits[$this->aid])) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění pracovat s touto skupinou!', 'danger');
        $this->redirect('this');
    }

    public function handleSend(int $pid) : void
    {
        $this->checkEditation();

        $payment = $this->model->findPayment($pid);

        if ($payment === null) {
            $this->flashMessage('Zadaná platba neexistuje', 'danger');
            $this->redirect('this');
        }

        if ($payment->getEmail() === null) {
            $this->flashMessage('Platba nemá vyplněný email', 'danger');
            $this->redirect('this');
        }

        try {
            $this->sendEmailsForPayments([$payment]);
        } catch (PaymentClosed $e) {
            $this->flashMessage('Nelze odeslat uzavřenou platbu');
        }
    }

    /**
     * rozešle všechny neposlané emaily
     *
     * @param int $gid groupId
     */
    public function handleSendGroup(int $gid) : void
    {
        $this->checkEditation();

        $payments = $this->getPaymentsForGroup($gid);

        $payments = array_filter(
            $payments,
            function (Payment $p) {
                return ! $p->isClosed() && $p->getEmail() !== null && $p->getState()->equalsValue(State::PREPARING);
            }
        );

        $this->sendEmailsForPayments($payments);
    }

    public function handleSendTest(int $gid) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Neplatný požadavek na odeslání testovacího emailu!', 'danger');
            $this->redirect('this');
        }

        try {
            $email = $this->mailing->sendTestMail($gid);
            $this->flashMessage('Testovací email byl odeslán na ' . $email . '.');
        } catch (MailCredentialsNotSet $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (InvalidSmtp $e) {
            $this->smtpError($e);
        } catch (InvalidBankAccount $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
        } catch (EmailNotSet $e) {
            $this->flashMessage('Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!', 'danger');
        }

        $this->redirect('this');
    }

    public function handleComplete(int $pid) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nejste oprávněni k uzavření platby!', 'danger');
            $this->redirect('this');
        }

        try {
            $this->model->completePayment($pid);
            $this->flashMessage('Platba byla zaplacena.');
        } catch (PaymentClosed $e) {
            $this->flashMessage('Tato platba už je uzavřená', 'danger');
        }

        $this->redirect('this');
    }

    public function handleGenerateVs(int $gid) : void
    {
        $group = $this->model->getGroup($gid);

        if (! $this->isEditable || $group === null || ! $this->hasAccessToGroup($group)) {
            $this->flashMessage('Nemáte oprávnění generovat VS!', 'danger');
            $this->redirect('Payment:default');
        }

        $nextVS = $this->model->getNextVS($group->getId());

        if ($nextVS === null) {
            $this->flashMessage('Vyplňte VS libovolné platbě a další pak již budou dogenerovány způsobem +1.', 'warning');
            $this->redirect('this');
        }

        $numberOfUpdatedVS = $this->model->generateVs($gid);
        $this->flashMessage('Počet dogenerovaných VS: ' . $numberOfUpdatedVS, 'success');
        $this->redirect('this');
    }

    public function handleCloseGroup(int $gid) : void
    {
        $group = $this->model->getGroup($gid);
        if (! $this->isEditable || $group === null || ! $this->hasAccessToGroup($group)) {
            $this->flashMessage('Nejste oprávněni úpravám akce!', 'danger');
            $this->redirect('this');
        }

        $userData = $this->userService->getUserDetail();
        $note     = 'Uživatel ' . $userData->Person . ' uzavřel skupinu plateb dne ' . date('j.n.Y H:i');

        try {
            $this->model->closeGroup($gid, $note);
        } catch (GroupNotFound $e) {
        }

        $this->redirect('this');
    }

    public function handleOpenGroup(int $gid) : void
    {
        $group = $this->model->getGroup($gid);

        if (! $this->isEditable || $group === null || ! $this->hasAccessToGroup($group)) {
            $this->flashMessage('Nejste oprávněni úpravám akce!', 'danger');
            $this->redirect('this');
        }

        $userData = $this->userService->getUserDetail();
        $note     = 'Uživatel ' . $userData->Person . ' otevřel skupinu plateb dne ' . date('j.n.Y H:i');

        try {
            $this->model->openGroup($gid, $note);
        } catch (GroupNotFound $e) {
        }

        $this->redirect('this');
    }

    public function handleOpenRemoveDialog() : void
    {
        /** @var RemoveGroupDialog $dialog */
        $dialog = $this['removeGroupDialog'];
        $dialog->open();
    }

    protected function createComponentPaymentForm() : Form
    {
        $form = new BaseForm();
        $form->useBootstrap4();
        $form->addText('name', 'Název/účel')
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte zadat název platby');
        $form->addText('amount', 'Částka')
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit částku')
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo')
            ->addRule(Form::MIN, 'Částka musí být větší než 0', 0.01);
        $form->addText('email', 'Email')
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'Zadaný email nemá platný formát');
        $form->addDate('maturity', 'Splatnost')
            ->setAttribute('class', 'form-control');
        $form->addVariableSymbol('vs', 'VS')
            ->setRequired(false)
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED);
        $form->addText('ks', 'KS')
            ->setMaxLength(4)
            ->setAttribute('class', 'form-control')
            ->addCondition(Form::FILLED)->addRule(Form::INTEGER, 'Konstantní symbol musí být číslo');
        $form->addText('note', 'Poznámka')
            ->setAttribute('class', 'form-control');
        $form->addHidden('oid');
        $form->addHidden('pid');
        $form->addSubmit('send', 'Přidat platbu')->setAttribute('class', 'btn btn-primary');

        $form->onSubmit[] = function (Form $form) : void {
            $this->paymentSubmitted($form);
        };

        return $form;
    }

    private function paymentSubmitted(Form $form) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nejste oprávněni k úpravám plateb!', 'danger');
            $this->redirect('this');
        }
        $v = $form->getValues();
        if ($v->maturity === null) {
            $form['maturity']->addError('Musíte vyplnit splatnost');
            return;
        }

        $id             = $v->pid !== '' ? (int) $v->pid : null;
        $name           = $v->name;
        $email          = $v->email !== '' ? $v->email : null;
        $amount         = (float) $v->amount;
        $dueDate        = $v->maturity;
        $variableSymbol = $v->vs;
        $constantSymbol = $v->ks !== '' ? (int) $v->ks : null;
        $note           = (string) $v->note;

        if ($id !== null) {//EDIT
            $this->model->update($id, $name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
            $this->flashMessage('Platba byla upravena');
        } else {//ADD
            $this->model->createPayment(
                (int) $v->oid,
                $name,
                $email,
                $amount,
                $dueDate,
                null,
                $variableSymbol,
                $constantSymbol,
                $note
            );
            $this->flashMessage('Platba byla přidána');
        }
        $this->redirect('detail', ['id' => $v->oid]);
    }

    protected function createComponentPairButton() : PairButton
    {
        return $this->pairButtonFactory->create();
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }

    protected function createComponentUnit() : GroupUnitControl
    {
        return $this->unitControlFactory->create($this->id);
    }

    protected function createComponentRemoveGroupDialog() : RemoveGroupDialog
    {
        if (! $this->isEditable) {
            throw new BadRequestException('Nemáte oprávnění mazat tuto skupinu');
        }

        return $this->removeGroupDialogFactory->create($this->id);
    }

    private function smtpError(InvalidSmtp $e) : void
    {
        $this->flashMessage(sprintf('SMTP server vrátil chybu (%s)', $e->getMessage()), 'danger');
        $this->flashMessage('V případě problémů s odesláním emailu přes gmail si nastavte možnost použití adresy méně bezpečným aplikacím viz https://support.google.com/accounts/answer/6010255?hl=cs', 'warning');
    }

    private function hasAccessToGroup(Group $group) : bool
    {
        return array_intersect($group->getUnitIds(), array_keys($this->readUnits)) !== [];
    }

    /**
     * @param Payment[] $payments
     */
    private function sendEmailsForPayments(array $payments) : void
    {
        $sentCount = 0;

        try {
            foreach ($payments as $payment) {
                $this->commandBus->handle(new SendPaymentInfo($payment->getId()));
                $sentCount++;
            }
        } catch (MailCredentialsNotSet $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
            $this->redirect('this');
        } catch (InvalidBankAccount $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
            $this->redirect('this');
        } catch (InvalidSmtp $e) {
            $this->smtpError($e);
            $this->redirect('this');
        }

        if ($sentCount > 0) {
            $this->flashMessage(
                $sentCount === 1
                    ? 'Informační email byl odeslán'
                    : 'Informační emaily (' . $sentCount . ') byly odeslány',
                'success'
            );
        }

        $this->redirect('this');
    }

    /**
     * @return Payment[]
     */
    private function getPaymentsForGroup(int $groupId) : array
    {
        return $this->queryBus->handle(new PaymentListQuery($groupId));
    }
}
