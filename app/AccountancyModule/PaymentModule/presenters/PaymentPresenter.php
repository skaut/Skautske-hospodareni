<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupProgress;
use App\AccountancyModule\PaymentModule\Components\GroupUnitControl;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Components\PaymentDialog;
use App\AccountancyModule\PaymentModule\Components\PaymentList;
use App\AccountancyModule\PaymentModule\Components\RemoveGroupDialog;
use App\AccountancyModule\PaymentModule\Factories\IGroupUnitControlFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IPairButtonFactory;
use App\AccountancyModule\PaymentModule\Factories\IPaymentDialogFactory;
use App\AccountancyModule\PaymentModule\Factories\IPaymentListFactory;
use App\AccountancyModule\PaymentModule\Factories\IRemoveGroupDialogFactory;
use DateTimeImmutable;
use Model\DTO\Payment\Payment;
use Model\DTO\Payment\Person;
use Model\Google\Exception\OAuthNotSet;
use Model\Google\InvalidOAuth;
use Model\Payment\Commands\Mailing\SendPaymentInfo;
use Model\Payment\EmailNotSet;
use Model\Payment\GroupNotFound;
use Model\Payment\InvalidBankAccount;
use Model\Payment\MailingService;
use Model\Payment\Payment\State;
use Model\Payment\PaymentClosed;
use Model\Payment\PaymentNotFound;
use Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\PaymentService;
use Model\UnitService;
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

    /** @var IPaymentDialogFactory */
    private $paymentDialogFactory;

    /** @var IPaymentListFactory */
    private $paymentListFactory;

    private const NO_MAILER_MESSAGE       = 'Nemáte nastavený mail pro odesílání u skupiny';
    private const NO_BANK_ACCOUNT_MESSAGE = 'Skupina nemá nastavený bankovní účet';

    public function __construct(
        PaymentService $model,
        UnitService $unitService,
        MailingService $mailing,
        IMassAddFormFactory $massAddFormFactory,
        IPairButtonFactory $pairButtonFactory,
        IGroupUnitControlFactory $unitControlFactory,
        IRemoveGroupDialogFactory $removeGroupDialogFactory,
        IPaymentDialogFactory $paymentDialogFactory,
        IPaymentListFactory $paymentListFactory
    ) {
        parent::__construct();
        $this->model                    = $model;
        $this->unitService              = $unitService;
        $this->mailing                  = $mailing;
        $this->massAddFormFactory       = $massAddFormFactory;
        $this->pairButtonFactory        = $pairButtonFactory;
        $this->unitControlFactory       = $unitControlFactory;
        $this->removeGroupDialogFactory = $removeGroupDialogFactory;
        $this->paymentDialogFactory     = $paymentDialogFactory;
        $this->paymentListFactory       = $paymentListFactory;
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
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění zobrazit detail plateb.']);

            return;
        }

        if ($this->canEditGroup($group)) {
            $this['pairButton']->setGroups([$id]);
        }

        $nextVS = $this->model->getNextVS($group->getId());

        $payments = $this->getPaymentsForGroup($id);

        $paymentsForSendEmail = $this->paymentsAvailableForGroupInfoSending($payments);

        $this->template->setParameters([
            'group' => $group,
            'nextVS' => $nextVS,
            'payments'  => $payments,
            'summarize' => $this->model->getGroupSummaries([$id])[$id],
            'now'       => new DateTimeImmutable(),
            'isGroupSendActive' => $group->getState() === 'open' && ! empty($paymentsForSendEmail),
            'notSentPaymentsCount' => $this->countNotSentPayments($payments),
        ]);
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

        $this->setView('accessDenied');
        $this->template->setParameters(['message' => 'Nemáte oprávnění pracovat s touto skupinou.']);
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

        $this->sendEmailsForPayments($this->paymentsAvailableForGroupInfoSending($payments));
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
        } catch (OAuthNotSet $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
        } catch (InvalidOAuth $e) {
            $this->oauthError($e);
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

    protected function createComponentPaymentDialog() : PaymentDialog
    {
        $this->assertCanEditGroup();

        $dialog = $this->paymentDialogFactory->create($this->id);

        $dialog->onSuccess[] = function () : void {
            $this->redrawControl('grid');
        };

        return $dialog;
    }

    protected function createComponentPaymentList() : PaymentList
    {
        return $this->paymentListFactory->create($this->id, $this->isEditable);
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

    protected function createComponentProgress() : GroupProgress
    {
        return new GroupProgress($this->model->getGroupSummaries([$this->id])[$this->id]);
    }

    private function oauthError(InvalidOAuth $e) : void
    {
        $this->flashMessage(sprintf('Google vrátil chybu: %s', $e->getMessage()), 'danger');
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
        } catch (OAuthNotSet $e) {
            $this->flashMessage(self::NO_MAILER_MESSAGE, 'warning');
            $this->redirect('this');
        } catch (InvalidBankAccount $e) {
            $this->flashMessage(self::NO_BANK_ACCOUNT_MESSAGE, 'warning');
            $this->redirect('this');
        } catch (InvalidOAuth $e) {
            $this->oauthError($e);
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

    /**
     * @param Payment[] $payments
     *
     * @return Payment[]
     */
    private function paymentsAvailableForGroupInfoSending(array $payments) : array
    {
        return array_filter(
            $payments,
            function (Payment $p) {
                return ! $p->isClosed() && $p->getEmail() !== null && $p->getSentEmails() === [];
            }
        );
    }

    /**
     * @param Payment[] $payments
     */
    private function countNotSentPayments(array $payments) : int
    {
        return count(array_filter($payments, fn (Payment $payment) => $payment->getSentEmails() === [] && $payment->getState() === State::PREPARING));
    }
}
