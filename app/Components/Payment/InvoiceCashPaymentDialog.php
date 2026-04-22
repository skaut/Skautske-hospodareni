<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\Dialog;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceRepository;
use Component\Forms\BaseForm;
use InvalidArgumentException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/** @method void onSuccess() */
final class InvoiceCashPaymentDialog extends Dialog
{
    /** @var callable[] */
    public array $onSuccess = [];

    /** @persistent */
    public int $invoiceId = -1;

    public function __construct(
        private readonly ?int $invoiceSequenceId,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InvoiceManager $invoiceManager,
    ) {
    }

    public function handleOpen(int $invoiceId = -1): void
    {
        $this->invoiceId = $invoiceId;

        $this->show();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/InvoiceCashPaymentDialog.latte');
        $this->template->setParameters([
            'invoice' => $this->invoice(),
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('cashReceiptNumber', 'Číslo příjmového dokladu')
            ->setRequired('Musíte zadat číslo příjmového dokladu.')
            ->setMaxLength(64);

        $form->addSubmit('send', 'Označit jako proplacenou');

        $invoice = $this->invoice();
        if ($invoice !== null && $invoice->getCashReceiptNumber() !== null) {
            $form->setDefaults([
                'cashReceiptNumber' => $invoice->getCashReceiptNumber(),
            ]);
        }

        $form->onSubmit[] = function (): void {
            $this->redrawControl();
        };

        $form->onSuccess[] = function (Form $form): void {
            $this->invoiceSubmitted($form->getValues());
        };

        return $form;
    }

    private function invoiceSubmitted(ArrayHash $values): void
    {
        $invoice = $this->invoice();
        if ($invoice === null) {
            $this->presenter->flashMessage('Zadaná faktura neexistuje.', 'danger');
            $this->hide();

            return;
        }

        try {
            $marked = $this->invoiceManager->markAsPaidInCash($invoice, $values->cashReceiptNumber);
        } catch (InvalidArgumentException $e) {
            $this->presenter->flashMessage($e->getMessage(), 'danger');
            $this->redrawControl();

            return;
        }

        $this->presenter->flashMessage(
            $marked ? 'Faktura byla označena jako proplacená v hotovosti.' : 'Faktura už je označena jako proplacená.',
            $marked ? 'success' : 'info',
        );

        $this->onSuccess();
        $this->hide();
    }

    private function invoice(): ?Invoice
    {
        if ($this->invoiceId === -1) {
            return null;
        }

        $invoice = $this->invoiceRepository->find($this->invoiceId);

        if (! $invoice instanceof Invoice) {
            return null;
        }

        if ($this->invoiceSequenceId !== null && $invoice->getSequence()->getId() !== $this->invoiceSequenceId) {
            return null;
        }

        return $invoice;
    }
}
