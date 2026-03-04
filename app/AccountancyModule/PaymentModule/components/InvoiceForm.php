<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Entity\Embeddable\InvoiceCustomer;
use Entity\Embeddable\InvoiceSupplier;
use Entity\Invoice;
use Entity\InvoiceItem;
use Entity\InvoiceSequence;
use Enum\InvoicePaymentType;
use Manager\InvoiceManager;
use Model\UnitService;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Repository\InvoiceRepository;
use Throwable;
use Utility\Ares\ViAresParser;

class InvoiceForm extends BaseControl
{
    private int $itemsCount = 0;

    public function __construct(
        private readonly InvoiceSequence $invoiceSequence,
        private readonly InvoiceManager $invoiceManager,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly UnitService $unitRepository,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/InvoiceForm.latte');
        $this->template->render();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addDate('dueDate', 'Datum splatnosti')
            ->setDefaultValue((new ChronosDate())->addDays($this->invoiceSequence->getDefaultDueDate()))
            ->addRule(Form::REQUIRED);

        $form->addDate('dateOfIssue', 'Datum vystavení')
            ->setDefaultValue(new ChronosDate())
            ->addRule(Form::REQUIRED);

        $form->addDate('dateOfTaxPayment', 'Datum zdanitelného plnění')
            ->setDefaultValue(new ChronosDate())
            ->addRule(Form::REQUIRED);

        $form->addText('issuedBy', 'Vystavil')->addRule(Form::REQUIRED);
        $form->addSelect('paymentType', 'Způsob platby', InvoicePaymentType::toSelect())
            ->setDefaultValue(InvoicePaymentType::CASH->name)
            ->setRequired();

        $customerContainer = $form->addContainer('customer');
        $customerContainer->addText('companyNumber', 'IČO');
        $customerContainer->addSubmit('ares', 'Získat z Aresu')
            ->setValidationScope([$customerContainer->getComponent('companyNumber')])
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary ajax')
            ->onClick[] = function (SubmitButton $button): void {
                $this->getContactInfo($button);
            };

        $customerContainer->addText('vat', 'DIČ');
        $customerContainer->addText('name', 'Název');
        $customerContainer->addText('street', 'Ulice');
        $customerContainer->addText('streetNumber', 'Číslo popisné');
        $customerContainer->addText('streetNumberSuffix', 'Číslo orientační');
        $customerContainer->addText('city', 'Město');
        $customerContainer->addText('zipCode', 'PSČ');

        $items = $form->addDynamic('items', function (Container $container): void {
            ++$this->itemsCount;
            $container->addText('purpose')
                ->setMaxLength(120)
                ->setRequired('Zadejte účel výplaty')
                ->setHtmlAttribute('placeholder', 'Účel');

            $container->addInteger('quantity')
                ->setHtmlAttribute('placeholder', 'Množstvní')
                ->setDefaultValue(1);
            $container->addText('unit')
                ->setHtmlAttribute('placeholder', 'Jednotka')
                ->setDefaultValue('ks');
            if ($this->invoiceSequence->isVatPayer()) {
                $container->addSelect('vat', 'Daň', [21 => '21%', 12 => '12%', 0 => '0%']);
            }
            $container->addText('price')
                ->setHtmlAttribute('placeholder', 'Cena za jednotku s DPH')
                ->setRequired('Zadejte cenu');

            $container->addSubmit('remove', 'Odebrat položku')
                ->setValidationScope([])
                ->setHtmlAttribute('class', 'btn btn-light btn-sm ajax')
                ->onClick[] = function (SubmitButton $button): void {
                    $this->removeItem($button);
                };
        }, 1);

        $items->addSubmit('addItem', 'Přidat další položku')
            ->setHtmlAttribute('class', 'btn btn-light ajax')
            ->setValidationScope([])
            ->onClick[] = function () use ($items): void {
                $items->createOne();
                $items->setValues(['quantity' => 1, 'price' => '0.00', 'vat' => '0.00', 'unit' => 'ks', 'purpose' => '']);
                $this->reload();
            };

        $form->addSubmit('send', 'Send');
        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() != $form['send']) {
                return;
            }

            $this->formSucceeded($form, $values);
        };

        return $form;
    }

    private function removeItem(SubmitButton $button): void
    {
        $container = $button->getParent();
        $replicator = $container->getParent();
        assert($replicator instanceof \Kdyby\Replicator\Container && $container instanceof Container);
        $replicator->remove($container, true);
        $this->reload();
    }

    public function getContactInfo(SubmitButton $button): void
    {
        $customerContainer = $button->getForm()->getComponent('customer');
        assert($customerContainer instanceof Container);

        $values = $customerContainer->getValues();
        $companyInfo = null;

        try {
            $companyInfo = (new ViAresParser())->getAres($values->companyNumber);
        } catch (Throwable $e) {
            dumpe($e);
        }

        if ($companyInfo !== null) {
            $customerContainer->setValues($companyInfo->toArray());
        }

        if ($this->presenter->isAjax()) {
            $this->redrawControl('form');
        } else {
            $this->presenter->redirect('this');
        }
    }

    public function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        $invoiceSupplier = InvoiceSupplier::fromOfficialUnit($this->unitRepository->getOfficialUnit(), $this->invoiceSequence->getVatNumber(), $this->invoiceSequence->isVatPayer());
        $invoiceCustomer = InvoiceCustomer::fromForm($values->customer);

        $invoice = Invoice::formForm($values, $this->invoiceSequence, $invoiceSupplier, $invoiceCustomer);

        foreach ($values['items'] as $item) {
            $invoice->addItem(InvoiceItem::fromForm($item));
        }

        $this->invoiceManager->create($invoice, $this->invoiceRepository);

        $this->presenter->flashMessage('Faktura byla vytvořena');
        if ($this->presenter->isAjax()) {
            $formComponent = $this->getComponent('form');
            assert($formComponent instanceof BaseForm);
            $formComponent->setValues([], true);
            $this->presenter->redrawControl('form');
        } else {
            $this->presenter->redirect('this');
        }
    }
}
