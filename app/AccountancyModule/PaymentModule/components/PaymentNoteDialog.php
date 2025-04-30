<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use Model\Commands\Payment\UpdateNote;
use Model\Common\Services\CommandBus;
use Model\DTO\Payment\Payment;
use Model\PaymentService;
use Nette\Application\UI\Form;

/** @method void onSuccess() */
final class PaymentNoteDialog extends Dialog
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
        parent::beforeRender();

        $this->template->setFile(__DIR__ . '/templates/PaymentNoteDialog.latte');
        $this->template->setParameters([
            'payment' => $this->payment(),
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('note', 'Poznámka');

        $payment = $this->payment();

        $form->addSubmit('send', 'Uložit poznámku');

        if ($payment !== null) {
            $form->setDefaults([
                'note' => $payment->getNote(),
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
        $values  = $form->getValues();
        $payment = $this->payment();
        if ($payment === null) {
            $this->presenter->flashMessage('Zadaná platba neexistuje', 'danger');
            $this->hide();

            return;
        }

        $this->commandBus->handle(
            new UpdateNote(
                $this->paymentId,
                $values->note,
            ),
        );
        $this->flashMessage('Poznámka byla upravena', 'success');

        $this->onSuccess();
        $this->hide();
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
