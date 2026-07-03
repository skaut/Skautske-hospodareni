<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\Dialog;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use Component\Forms\BaseForm;
use InvalidArgumentException;
use LogicException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

final class InvoiceDuplicateDialog extends Dialog
{
    /** @persistent */
    public int $invoiceId = -1;

    /**
     * @param int[] $editableUnitIds
     */
    public function __construct(
        private readonly ?int $invoiceSequenceId,
        private readonly array $editableUnitIds,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InvoiceSequenceRepository $invoiceSequenceRepository,
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

        $this->template->setFile(__DIR__.'/templates/InvoiceDuplicateDialog.latte');
        $this->template->setParameters([
            'invoice' => $this->invoice(),
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addSelect('targetSequenceId', 'Cílová fakturační řada', $this->targetSequenceOptions())
            ->setPrompt('Vyberte cílovou fakturační řadu')
            ->setRequired('Vyberte cílovou fakturační řadu.');

        $form->addSubmit('send', 'Duplikovat fakturu')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg w-100 mt-2 ajax');

        $form->onSubmit[] = function (): void {
            $this->redrawControl();
        };

        $form->onSuccess[] = function (Form $form): void {
            $this->duplicateInvoice($form->getValues());
        };

        return $form;
    }

    /**
     * @return array<int, string>
     */
    private function targetSequenceOptions(): array
    {
        $invoice = $this->invoice();
        if ($invoice === null) {
            return [];
        }

        $options = [];
        foreach ($this->invoiceSequenceRepository->findOpenAccessibleByUnits($this->editableUnitIds) as $sequence) {
            if ($sequence->getId() === $invoice->getSequence()->getId()) {
                continue;
            }

            if (
                $invoice->getPaymentType()->value === InvoicePaymentType::TRANSFER->value
                && $sequence->getBankAccount() === null
            ) {
                continue;
            }

            $options[$sequence->getId()] = $sequence->getDisplayLabel();
        }

        return $options;
    }

    private function duplicateInvoice(ArrayHash $values): void
    {
        $invoice = $this->invoice();
        if ($invoice === null) {
            $this->presenter->flashMessage('Zadaná faktura neexistuje.', 'danger');
            $this->hide();

            return;
        }

        $targetSequence = $this->invoiceSequenceRepository->findAccessibleByUnits(
            (int) $values->targetSequenceId,
            $this->editableUnitIds,
        );
        if (! $targetSequence instanceof InvoiceSequence || ! $targetSequence->isOpen()) {
            $this->flashMessage('Vybraná fakturační řada není dostupná.', 'danger');
            $this->redrawControl();

            return;
        }

        try {
            $duplicatedInvoice = $this->invoiceManager->duplicateToSequence($invoice, $targetSequence);
        } catch (InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redrawControl();

            return;
        }

        $this->presenter->flashMessage('Faktura byla zduplikována.', 'success');
        $this->hide();
        $this->presenter->redirect(':Payments:InvoiceList:edit', ['id' => $duplicatedInvoice->getId()]);
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

        if ($this->invoiceSequenceId === null) {
            throw new LogicException('Dialog duplikace faktury lze použít jen v kontextu fakturační řady.');
        }

        if ($invoice->getSequence()->getId() !== $this->invoiceSequenceId) {
            return null;
        }

        return $invoice;
    }
}
