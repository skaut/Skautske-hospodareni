<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use App\MyValidators;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Model\Common\EmailAddress;
use Model\Common\Services\CommandBus;
use Model\DTO\Payment\Payment;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\Commands\Payment\UpdatePayment;
use Model\Payment\InvalidVariableSymbol;
use Model\PaymentService;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

use function array_map;
use function explode;
use function implode;
use function preg_replace;

/** @method void onSuccess() */
final class PaymentDialog extends Dialog
{
    /** @var callable[] */
    public array $onSuccess = [];

    /** @persistent */
    public int $paymentId = -1;

    public function __construct(private int $groupId, private CommandBus $commandBus, private PaymentService $paymentService)
    {
    }

    public function handleOpen(int $paymentId = -1): void
    {
        $this->paymentId = $paymentId;

        $this->show();
    }

    protected function beforeRender(): void
    {
        $this->template->setFile(__DIR__ . '/templates/PaymentDialog.latte');
        $this->template->setParameters([
            'payment' => $this->payment(),
            'editing' => $this->isEditing(),
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('name', 'Název')
            ->addRule(Form::FILLED, 'Musíte zadat název platby');

        $form->addText('amount', 'Částka')
            ->addRule(Form::FILLED, 'Musíte vyplnit částku')
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo')
            ->addRule(Form::MIN, 'Částka musí být větší než 0', 0.01);

        $form->addText('email', 'E-mail')
            ->setRequired(false)
            ->addFilter(fn (string $value) => preg_replace('/\s+/', '', $value))
            ->setNullable()
            ->addCondition(Form::FILLED)
            ->addRule([MyValidators::class, 'isValidEmailList'], 'Zadaný e-mail nemá platný formát. Více adres oddělte pouze čárkou.');

        $form->addDate('dueDate', 'Splatnost')
            ->disableWeekends()
            ->setRequired('Musíte vyplnit splatnost');

        $form->addVariableSymbol('variableSymbol', 'VS')
            ->setRequired(false);

        $form->addText('constantSymbol', 'KS')
            ->setNullable()
            ->setMaxLength(4)
            ->setHtmlType('text')
            ->setRequired(false)
            ->addRule(Form::INTEGER, 'KS musí být číslo');

        $form->addText('note', 'Poznámka');

        $payment = $this->payment();

        $form->addSubmit('send', $payment === null ? 'Přidat platbu' : 'Uložit platbu');

        if ($payment !== null) {
            $form->setDefaults([
                'name' => $payment->getName(),
                'amount' => $payment->getAmount(),
                'email' => implode(MyValidators::EMAIL_SEPARATOR, $payment->getEmailRecipients()),
                'dueDate' => $payment->getDueDate(),
                'variableSymbol' => $payment->getVariableSymbol(),
                'constantSymbol' => $payment->getConstantSymbol(),
                'note' => $payment->getNote(),
            ]);
        } else {
            $group = $this->paymentService->getGroup($this->groupId);
            Assertion::notNull($group);

            try {
                $nextVS = $this->paymentService->getNextVS($this->groupId);
            } catch (InvalidVariableSymbol $exception) {
                $this->flashMessage('Nelze vygenerovat následující VS: \'' . $exception->getInvalidValue() . '\'', 'danger');
                $nextVS = '';
            }

            $form->setDefaults([
                'amount' => $group->getDefaultAmount(),
                'dueDate' => $group->getDueDate(),
                'variableSymbol' => $nextVS !== null ? (string) $nextVS : '',
                'constantSymbol' => $group->getConstantSymbol(),
            ]);
        }

        $form->onSubmit[] = function (): void {
            $this->redrawControl();
        };

        $form->onSuccess[] = function (Form $form): void {
            $this->paymentSubmitted($form);
        };

        return $form;
    }

    private function paymentSubmitted(Form $form): void
    {
        $v = $form->getValues();

        if ($this->isEditing()) {
            $this->updatePayment($v);
        } else {
            $this->createPayment($v);
        }

        $this->onSuccess();
        $this->hide();
    }

    private function updatePayment(ArrayHash $values): void
    {
        $payment = $this->payment();

        if ($payment === null) {
            $this->presenter->flashMessage('Zadaná platba neexistuje', 'danger');
            $this->hide();

            return;
        }

        $this->commandBus->handle(
            new UpdatePayment(
                $this->paymentId,
                $values->name,
                $this->processEmails($values->email),
                $values->amount,
                new ChronosDate($values->dueDate),
                $values->variableSymbol,
                $values->constantSymbol,
                $values->note,
            ),
        );
        $this->flashMessage('Platba byla upravena', 'success');
    }

    /** @return EmailAddress[] */
    private function processEmails(string|null $emails): array
    {
        if ($emails === null) {
            return [];
        }

        return array_map(
            fn (string $email) => new EmailAddress($email),
            explode(MyValidators::EMAIL_SEPARATOR, $emails),
        );
    }

    private function createPayment(ArrayHash $values): void
    {
        $this->commandBus->handle(
            new CreatePayment(
                $this->groupId,
                $values->name,
                $this->processEmails($values->email),
                $values->amount,
                new ChronosDate($values->dueDate),
                null,
                $values->variableSymbol,
                $values->constantSymbol,
                $values->note,
            ),
        );

        $this->flashMessage('Platba byla přidána', 'success');
        $this->hide();
    }

    private function isEditing(): bool
    {
        return $this->paymentId !== -1;
    }

    private function payment(): Payment|null
    {
        if ($this->paymentId === -1) {
            return null;
        }

        $payment = $this->paymentService->findPayment($this->paymentId);

        if ($payment === null || $payment->getGroupId() !== $this->groupId) {
            return null;
        }

        return $payment;
    }
}
