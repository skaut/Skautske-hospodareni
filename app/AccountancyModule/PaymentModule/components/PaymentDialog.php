<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Assert\Assertion;
use eGen\MessageBus\Bus\CommandBus;
use Model\DTO\Payment\Payment;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\Payment\Commands\Payment\UpdatePayment;
use Model\PaymentService;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

final class PaymentDialog extends BaseControl
{
    /**
     * @persistent
     * @var int
     */
    public $paymentId = -1;

    /**
     * @persistent
     * @var bool
     */
    public $open = false;

    /** @var int */
    private $groupId;

    /** @var CommandBus */
    private $commandBus;

    /** @var PaymentService */
    private $paymentService;

    public function __construct(int $groupId, CommandBus $commandBus, PaymentService $paymentService)
    {
        parent::__construct();
        $this->groupId        = $groupId;
        $this->commandBus     = $commandBus;
        $this->paymentService = $paymentService;
    }

    public function handleOpen(int $paymentId = -1) : void
    {
        $this->paymentId = $paymentId;
        $this->open      = true;

        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setParameters([
            'payment' => $this->payment(),
            'editing' => $this->isEditing(),
            'renderModal' => $this->open,
        ]);

        $this->template->setFile(__DIR__ . '/templates/PaymentDialog.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->useBootstrap4();

        $form->addText('name', 'Název')
            ->addRule(Form::FILLED, 'Musíte zadat název platby');

        $form->addText('amount', 'Částka')
            ->addRule(Form::FILLED, 'Musíte vyplnit částku')
            ->addRule(Form::FLOAT, 'Částka musí být zadaná jako číslo')
            ->addRule(Form::MIN, 'Částka musí být větší než 0', 0.01);

        $form->addText('email', 'Email')
            ->setNullable()
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'Zadaný email nemá platný formát');

        $form->addDate('dueDate', 'Splatnost')
            ->setRequired('Musíte vyplnit splatnost');

        $form->addVariableSymbol('variableSymbol', 'VS')
            ->setRequired(false);

        $form->addText('constantSymbol', 'KS')
            ->setNullable()
            ->setMaxLength(4)
            ->setType('text')
            ->setRequired(false)
            ->addRule(Form::INTEGER, 'KS musí být číslo');

        $form->addText('note', 'Poznámka');

        $form->addSubmit('send', 'Přidat platbu');

        $payment = $this->payment();

        if ($payment !== null) {
            $form->setDefaults([
                'name' => $payment->getName(),
                'amount' => $payment->getAmount(),
                'email' => $payment->getEmail(),
                'dueDate' => $payment->getDueDate(),
                'variableSymbol' => $payment->getVariableSymbol(),
                'constantSymbol' => $payment->getConstantSymbol(),
                'note' => $payment->getNote(),
            ]);
        } else {
            $group = $this->paymentService->getGroup($this->groupId);
            Assertion::notNull($group);

            $nextVS = $this->paymentService->getNextVS($this->groupId);

            $form->setDefaults([
                'amount' => $group->getDefaultAmount(),
                'dueDate' => $group->getDueDate(),
                'variableSymbol' => $nextVS !== null ? (string) $nextVS : '',
                'constantSymbol' => $group->getConstantSymbol(),
            ]);
        }

        $form->onSubmit[] = function () : void {
            $this->redrawControl();
        };

        $form->onSuccess[] = function (Form $form) : void {
            $this->paymentSubmitted($form);
        };

        return $form;
    }

    private function paymentSubmitted(Form $form) : void
    {
        $v = $form->getValues();

        if ($this->isEditing()) {
            $this->updatePayment($v);
        } else {
            $this->createPayment($v);
        }

        $this->close();
    }

    private function updatePayment(ArrayHash $values) : void
    {
        $payment = $this->payment();

        if ($payment === null) {
            $this->presenter->flashMessage('Zadaná platba neexistuje', 'danger');
            $this->close();

            return;
        }

        $this->commandBus->handle(
            new UpdatePayment(
                $this->paymentId,
                $values->name,
                $values->email,
                $values->amount,
                $values->dueDate,
                $values->variableSymbol,
                $values->constantSymbol,
                $values->note
            )
        );
        $this->flashMessage('Platba byla upravena', 'success');
    }

    private function createPayment(ArrayHash $values) : void
    {
        $this->commandBus->handle(
            new CreatePayment(
                $this->groupId,
                $values->name,
                $values->email,
                $values->amount,
                $values->dueDate,
                null,
                $values->variableSymbol,
                $values->constantSymbol,
                $values->note
            )
        );

        $this->flashMessage('Platba byla přidána', 'success');
        $this->close();
    }

    private function isEditing() : bool
    {
        return $this->paymentId !== -1;
    }

    private function payment() : ?Payment
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

    private function close() : void
    {
        $this->paymentId = -1;
        $this->open      = false;

        $this->redrawControl();
    }
}
