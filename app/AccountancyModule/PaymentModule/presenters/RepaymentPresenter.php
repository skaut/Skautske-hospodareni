<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\RepaymentCandidate;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\BankAccountService;
use Model\Payment\BankError;
use Model\Payment\Commands\Repayment\CreateRepayments;
use Model\Payment\ReadModel\Queries\RepaymentCandidateListQuery;
use Model\Payment\Repayment;
use Model\PaymentService;
use Model\Utils\MoneyFactory;
use Nette\Forms\Control;

use function assert;
use function count;
use function sprintf;

final class RepaymentPresenter extends BasePresenter
{
    private Group $group;

    public function __construct(private PaymentService $payments, private BankAccountService $bankAccounts)
    {
        parent::__construct();
    }

    public function actionDefault(int $id): void
    {
        $group = $this->group = $this->payments->getGroup($id);

        if ($group === null && ! $this->isEditable) {
            $this->flashMessage('K této skupině nemáte přístup');
            $this->redirect('GroupList:');
        }

        $this->template->setParameters(['group' => $group]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addDate('date', 'Datum splatnosti:')
            ->disableWeekends()
            ->setRequired(true)
            ->setDefaultValue(Date::now()->addWeekday());

        $form->addSubmit('send', 'Založit příkazy k úhradě')
            ->setHtmlAttribute('class', 'btn btn-primary btn-large');

        $paymentsContainer = $form->addContainer('payments');

        foreach ($this->queryBus->handle(new RepaymentCandidateListQuery($this->group->getId())) as $repayment) {
            assert($repayment instanceof RepaymentCandidate);
            $container = $paymentsContainer->addContainer('payment' . $repayment->getPaymentId());

            $checkbox = $container->addCheckbox('selected');

            $container->addText('name')
                ->setDefaultValue('Vratka - ' . $repayment->getName() . ' - ' . $this->group->getName())
                ->addConditionOn($checkbox, $form::EQUAL, true)
                ->setRequired('Zadejte název vratky!');

            $container->addText('amount')
                ->setDefaultValue($repayment->getAmount())
                ->addConditionOn($checkbox, $form::EQUAL, true)
                ->setRequired('Zadejte částku vratky u ' . $repayment->getName())
                ->addRule($form::NUMERIC, 'Vratka musí být číslo!');

            $invalidBankAccountMessage = 'Zadejte platný bankovní účet u ' . $repayment->getName();
            $container->addText('account')
                ->setDefaultValue($repayment->getBankAccount() ?? '')
                ->setRequired(false)
                ->addConditionOn($checkbox, $form::EQUAL, true)
                ->setRequired('Musíte vyplnit bankovní účet')
                ->addRule($form::PATTERN, $invalidBankAccountMessage, '^([0-9]{1,6}-)?[0-9]{1,10}/[0-9]{4}$')
                ->addRule(
                    function (Control $control) {
                        return AccountNumber::isValid($control->getValue());
                    },
                    $invalidBankAccountMessage,
                );
        }

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->repaymentFormSubmitted($form);
        };

        return $form;
    }

    private function repaymentFormSubmitted(BaseForm $form): void
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
                MoneyFactory::fromFloat((float) $repayment->amount),
                $repayment->name,
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
                    $bankAccount->getToken(),
                ),
            );

            $this->flashMessage('Vratky byly odeslány do banky', 'success');
            $this->redirect('Payment:default', ['id' => $this->group->getId()]);
        } catch (BankError $e) {
            $form->addError(sprintf('Chyba z banky %s', $e->getMessage()));
        }
    }
}
