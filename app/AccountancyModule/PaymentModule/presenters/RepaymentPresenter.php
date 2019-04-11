<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\Payment;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccountService;
use Model\Payment\BankError;
use Model\Payment\Commands\Repayment\CreateRepayments;
use Model\Payment\Payment\State;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\Payment\Repayment;
use Model\PaymentService;
use Model\Utils\MoneyFactory;
use Nette\Forms\IControl;
use function array_filter;
use function count;
use function sprintf;

final class RepaymentPresenter extends BasePresenter
{
    /** @var Group */
    private $group;

    /** @var PaymentService */
    private $payments;

    /** @var BankAccountService */
    private $bankAccounts;

    public function __construct(PaymentService $payments, BankAccountService $bankAccounts)
    {
        parent::__construct();
        $this->payments     = $payments;
        $this->bankAccounts = $bankAccounts;
    }

    public function actionDefault(int $id) : void
    {
        $group = $this->group = $this->payments->getGroup($id);

        if ($group === null && ! $this->isEditable) {
            $this->flashMessage('K této skupině nemáte přístup');
            $this->redirect('Payment:default');
        }

        $this->template->setParameters(['group' => $group]);
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addDate('date', 'Datum splatnosti:')
            ->setDefaultValue(Date::now()->addWeekday());

        $form->addSubmit('send', 'Odeslat platby do banky')
            ->setAttribute('class', 'btn btn-primary btn-large');

        $paymentsContainer = $form->addContainer('payments');

        foreach ($this->getRepaymentCandidates($this->group->getId()) as $payment) {
            $container = $paymentsContainer->addContainer('payment' . $payment->getId());

            $checkbox = $container->addCheckbox('selected');

            $container->addText('name')
                ->setDefaultValue('Vratka - ' . $payment->getName() . ' - ' . $this->group->getName())
                ->addConditionOn($checkbox, $form::EQUAL, true)
                ->setRequired('Zadejte název vratky!');

            $container->addText('amount')
                ->setDefaultValue($payment->getAmount())
                ->addConditionOn($checkbox, $form::EQUAL, true)
                ->setRequired('Zadejte částku vratky u ' . $payment->getName())
                ->addRule($form::NUMERIC, 'Vratka musí být číslo!');

            $transaction = $payment->getTransaction();
            $account     = $transaction !== null ? (string) $transaction->getBankAccount() : '';

            $invalidBankAccountMessage = 'Zadejte platný bankovní účet u ' . $payment->getName();
            $container->addText('account')
                ->setDefaultValue($account)
                ->setRequired(false)
                ->addConditionOn($checkbox, $form::EQUAL, true)
                ->setRequired('Musíte vyplnit bankovní účet')
                ->addRule($form::PATTERN, $invalidBankAccountMessage, '^([0-9]{1,6}-)?[0-9]{1,10}/[0-9]{4}$')
                ->addRule(
                    function (IControl $control) {
                        return AccountNumber::isValid($control->getValue());
                    },
                    $invalidBankAccountMessage
                );
        }

        $form->onSubmit[] = function (BaseForm $form) : void {
            $this->repaymentFormSubmitted($form);
        };

        return $form;
    }

    private function repaymentFormSubmitted(BaseForm $form) : void
    {
        $values = $form->getValues();

        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění pro práci s platbami jednotky', 'danger');
            $this->redirect('Payment:default', ['id' => $this->group->getId()]);
        }

        $repayments = [];

        foreach ($form->values->payments as $repayment) {
            if (! $repayment->selected) {
                continue;
            }

            $repayments[] = new Repayment(
                AccountNumber::fromString($repayment->account),
                MoneyFactory::fromFloat($repayment->amount),
                $repayment->name
            );
        }

        if (count($repayments) === 0) {
            $form->addError('Nebyl vybrán žádný záznam k vrácení!');
            return;
        }

        $bankAccountId = $this->group->getBankAccountId();
        $bankAccount   = $bankAccountId !== null ? $this->bankAccounts->find($bankAccountId) : null;

        if ($bankAccount === null) {
            $this->flashMessage('Skupina plateb nemá nastavený bankovní účet', 'danger');
            $this->redirect('this');
        }

        if ($bankAccount->getToken() === null) {
            $this->flashMessage('Bankovní účet nemá nastavený token', 'danger');
            $this->redirect('this');
        }

        try {
            $this->commandBus->handle(
                new CreateRepayments(
                    $bankAccount->getNumber(),
                    $values->date ?? Date::now(),
                    $repayments,
                    $bankAccount->getToken()
                )
            );

            $this->flashMessage('Vratky byly odeslány do banky', 'success');
            $this->redirect('Payment:detail', ['id' => $this->group->getId()]);
        } catch (BankError $e) {
            $form->addError(sprintf('Chyba z banky %s', $e->getMessage()));
        }
    }

    /**
     * @return Payment[]
     */
    private function getRepaymentCandidates(int $groupId) : array
    {
        return array_filter(
            $this->queryBus->handle(new PaymentListQuery($groupId)),
            function (Payment $payment) : bool {
                return $payment->getState()->equalsValue(State::COMPLETED);
            }
        );
    }
}
