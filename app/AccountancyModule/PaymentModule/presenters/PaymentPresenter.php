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
use DateTimeImmutable;
use Model\DTO\Payment\Payment;
use Model\DTO\Payment\Person;
use Model\Payment\Commands\Mailing\SendPaymentInfo;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\Commands\Payment\UpdatePayment;
use Model\Payment\EmailNotSet;
use Model\Payment\GroupNotFound;
use Model\Payment\InvalidBankAccount;
use Model\Payment\InvalidSmtp;
use Model\Payment\MailCredentialsNotSet;
use Model\Payment\MailingService;
use Model\Payment\Payment\State;
use Model\Payment\PaymentClosed;
use Model\Payment\PaymentNotFound;
use Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\PaymentService;
use Model\UnitService;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use function array_filter;
use function assert;
use function count;
use function date;
use function sprintf;

class PaymentPresenter extends BasePresenter
{
    /**
     * @var        int
     * @persistent
     */
    public $id = 0;

    /** @var string[] */
    protected $readUnits;

    /** @var UnitService */
    protected $unitService;

    /** @var PaymentService */
    private $model;

    /** @var MailingService */
    private $mailing;

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
        IMassAddFormFactory $massAddFormFactory,
        IPairButtonFactory $pairButtonFactory,
        IGroupUnitControlFactory $unitControlFactory,
        IRemoveGroupDialogFactory $removeGroupDialogFactory
    ) {
        parent::__construct();
        $this->model                    = $model;
        $this->unitService              = $unitService;
        $this->mailing                  = $mailing;
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

    public function actionDefault(int $id) : void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->hasAccessToGroup($group)) {
            $this->flashMessage('Nemáte oprávnění zobrazit detail plateb', 'warning');
            $this->redirect('GroupList:');
        }

        if ($this->canEditGroup($group)) {
            $this['pairButton']->setGroups([$id]);
        }

        $nextVS = $this->model->getNextVS($group->getId());
        $form   = $this['paymentForm'];
        $form->setDefaults([
            'amount' => $group->getDefaultAmount(),
            'maturity' => $group->getDueDate(),
            'ks' => $group->getConstantSymbol(),
            'oid' => $group->getId(),
            'vs' => $nextVS !== null ? (string) $nextVS : '',
        ]);

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
            'now'       => new DateTimeImmutable(),
            'isGroupSendActive' => $group->getState() === 'open' && ! empty($paymentsForSendEmail),
        ]);
    }

    public function actionEdit(int $pid) : void
    {
        $payment = $this->model->findPayment($pid);

        if ($payment === null || $payment->isClosed()) {
            $this->flashMessage('Platba nenalezena', 'warning');
            $this->redirect('GroupList:');
        }

        $this->assertCanEditGroup();

        $form = $this['paymentForm'];

        $submit = $form['send'];

        assert($submit instanceof SubmitButton);

        $submit->caption = 'Upravit';

        $form->setDefaults([
            'name' => $payment->getName(),
            'email' => $payment->getEmail(),
            'amount' => $payment->getAmount(),
            'maturity' => $payment->getDueDate(),
            'vs' => $payment->getVariableSymbol(),
            'ks' => $payment->getConstantSymbol(),
            'note' => $payment->getNote(),
            'oid' => $payment->getGroupId(),
            'pid' => $pid,
        ]);

        $this->template->setParameters(['group' => $this->model->getGroup($payment->getGroupId())]);
    }

    /**
     * @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionMassAdd(int $id, ?int $unitId = null) : void
    {
        $this->assertCanEditGroup();

        $group = $this->model->getGroup($id);

        $form = $this['massAddForm'];
        $list = $this->queryBus->handle(new MembersWithoutPaymentInGroupQuery($this->unitId, $id));

        foreach ($list as $p) {
            assert($p instanceof Person);

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
        $this->assertCanEditGroup();

        try {
            $this->model->cancelPayment($pid);
        } catch (PaymentNotFound $e) {
            $this->flashMessage('Platba nenalezena!', 'danger');
        } catch (PaymentClosed $e) {
            $this->flashMessage('Tato platba už je uzavřená', 'danger');
        }
        $this->redirect('this');
    }

    private function assertCanEditGroup() : void
    {
        $group = $this->model->getGroup($this->id);

        if ($group !== null && $this->canEditGroup($group)) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění pracovat s touto skupinou!', 'danger');
        $this->redirect('Payment:default', ['id' => $this->id]);
    }

    public function handleSend(int $pid) : void
    {
        $this->assertCanEditGroup();

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
        $this->assertCanEditGroup();

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
        } catch (InvalidSmtp $e) {
            $this->flashMessage('Platba byla zaplacena, ale nepodařilo se odeslat informační email!', 'warning');
            $this->smtpError($e);
            $this->redirect('this');
        }

        $this->redirect('this');
    }

    public function handleGenerateVs() : void
    {
        $this->assertCanEditGroup();

        $nextVS = $this->model->getNextVS($this->id);

        if ($nextVS === null) {
            $this->flashMessage('Vyplňte VS libovolné platbě a další pak již budou dogenerovány způsobem +1.', 'warning');
            $this->redirect('this');
        }

        $numberOfUpdatedVS = $this->model->generateVs($this->id);
        $this->flashMessage('Počet dogenerovaných VS: ' . $numberOfUpdatedVS, 'success');
        $this->redirect('this');
    }

    public function handleCloseGroup() : void
    {
        $this->assertCanEditGroup();

        $userData = $this->userService->getUserDetail();
        $note     = 'Uživatel ' . $userData->Person . ' uzavřel skupinu plateb dne ' . date('j.n.Y H:i');

        try {
            $this->model->closeGroup($this->id, $note);
        } catch (GroupNotFound $e) {
        }

        $this->redirect('this');
    }

    public function handleOpenGroup() : void
    {
        $this->assertCanEditGroup();

        $userData = $this->userService->getUserDetail();
        $note     = 'Uživatel ' . $userData->Person . ' otevřel skupinu plateb dne ' . date('j.n.Y H:i');

        try {
            $this->model->openGroup($this->id, $note);
        } catch (GroupNotFound $e) {
        }

        $this->redirect('this');
    }

    public function handleOpenRemoveDialog() : void
    {
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
            ->setRequired('Musíte vyplnit splatnost')
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

        $form->onSuccess[] = function (Form $form) : void {
            $this->paymentSubmitted($form);
        };

        return $form;
    }

    private function paymentSubmitted(Form $form) : void
    {
        $this->assertCanEditGroup();

        $v = $form->getValues();

        $id             = $v->pid !== '' ? (int) $v->pid : null;
        $name           = $v->name;
        $email          = $v->email !== '' ? $v->email : null;
        $amount         = (float) $v->amount;
        $dueDate        = $v->maturity;
        $variableSymbol = $v->vs;
        $constantSymbol = $v->ks !== '' ? (int) $v->ks : null;
        $note           = (string) $v->note;

        if ($id !== null) {//EDIT
            $this->commandBus->handle(
                new UpdatePayment($id, $name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note)
            );
            $this->flashMessage('Platba byla upravena');
        } else {//ADD
            $this->commandBus->handle(
                new CreatePayment(
                    $this->id,
                    $name,
                    $email,
                    $amount,
                    $dueDate,
                    null,
                    $variableSymbol,
                    $constantSymbol,
                    $note
                )
            );

            $this->flashMessage('Platba byla přidána');
        }
        $this->redirect('default');
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
        $group = $this->model->getGroup($this->id);

        return $this->removeGroupDialogFactory->create($this->id, $group !== null && $this->canEditGroup($group));
    }

    private function smtpError(InvalidSmtp $e) : void
    {
        $this->flashMessage(sprintf('SMTP server vrátil chybu (%s)', $e->getMessage()), 'danger');
        $this->flashMessage('V případě problémů s odesláním emailu přes gmail si nastavte možnost použití adresy méně bezpečným aplikacím viz https://support.google.com/accounts/answer/6010255?hl=cs', 'warning');
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
