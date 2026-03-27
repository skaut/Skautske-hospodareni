<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Common\EmailAddress;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Payment\VariableSymbolCollision;
use App\Model\Unit\UnitService;
use App\MyValidators;
use Brick\Math\BigDecimal;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Nette\Forms\Container;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Throwable;
use Utility\Ares\ViAresParser;

use function array_map;
use function explode;
use function in_array;
use function preg_replace;
use function trim;

class InvoiceForm extends BaseControl
{
    private int $itemsCount = 0;

    public function __construct(
        private readonly InvoiceSequence $invoiceSequence,
        private readonly InvoiceManager $invoiceManager,
        private readonly UnitService $unitRepository,
        private readonly InvoiceUnitSettingRepository $invoiceUnitSettings,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/InvoiceForm.latte');
        $this->template->invoiceSequence = $this->invoiceSequence;
        $this->template->invoiceNumberPattern = $this->invoiceSequence->formatInvoiceNumber($this->invoiceSequence->getFirstNumberValue());
        $this->template->variableSymbolPattern = (string) $this->invoiceSequence->generateVariableSymbol($this->invoiceSequence->getFirstNumberValue());
        $this->template->hasBankAccount = $this->invoiceSequence->getBankAccount() !== null;
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

        $form->addText('issuedBy', 'Vystavil')->addRule(Form::REQUIRED);
        $paymentType = $form->addSelect('paymentType', 'Způsob platby', $this->paymentTypeOptions())
            ->setDefaultValue($this->defaultPaymentType()->name)
            ->setRequired();

        if ($this->invoiceSequence->getBankAccount() === null) {
            $paymentType->setOption('description', 'Bez nastaveného bankovního účtu lze vystavit jen fakturu s úhradou v hotovosti.');
        }
        $form->addText('email', 'E-mail příjemce')
            ->setOption('description', 'Nepovinné. Pokud není vyplněn, fakturu nebude možné odeslat e-mailem.')
            ->addFilter(fn (string $value) => preg_replace('/\s+/', '', $value))
            ->addRule([MyValidators::class, 'isValidEmailList'], 'Zadaný e-mail nemá platný formát. Více adres oddělte pouze čárkou.');

        $customerContainer = $form->addContainer('customer');
        $customerType = $customerContainer->addRadioList('type', 'Typ odběratele', [
            'company' => 'Firma',
            'person' => 'Fyzická osoba',
            'anonymous' => 'Bez identifikace odběratele',
        ]);
        assert($customerType instanceof RadioList);
        $customerType->setDefaultValue('company')
            ->setRequired('Vyberte typ odběratele');

        $customerContainer->addText('companyNumber', 'IČO')
            ->addFilter(fn (string $value) => trim((string) preg_replace('/\s+/', '', $value)));
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
            $priceLabel = 'Cena za jednotku';
            $container->addText('price', $priceLabel)
                ->setHtmlAttribute('placeholder', $priceLabel)
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
                $defaultValues = ['quantity' => 1, 'price' => '0.00', 'unit' => 'ks', 'purpose' => ''];

                $items->setValues($defaultValues);
                $this->reload();
            };

        $form->addSubmit('send', 'Vystavit');
        $form->onValidate[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }

            $this->validateInvoiceData($form, $values);
        };
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
        $form = $button->getForm();
        assert($form instanceof BaseForm);

        $customerContainer = $button->getForm()->getComponent('customer');
        assert($customerContainer instanceof Container);

        $values = $customerContainer->getValues();
        if (($values->companyNumber ?? '') === '') {
            $this->reload('Pro načtení z ARES zadejte IČO.', 'warning');

            return;
        }

        try {
            $companyInfo = (new ViAresParser())->getAres($values->companyNumber);
        } catch (Throwable) {
            $this->reload('Nepodařilo se načíst údaje z ARES.', 'danger');

            return;
        }

        if ($companyInfo->isEmpty()) {
            $this->reload('V ARES nebyly nalezeny údaje pro zadané IČO.', 'warning');

            return;
        }

        $form->setValues([
            'customer' => [
                'type' => 'company',
                'companyNumber' => $companyInfo->getCompanyName() ?? $values->companyNumber,
                'vat' => $companyInfo->getVat(),
                'name' => $companyInfo->getName(),
                'street' => $companyInfo->getStreet(),
                'streetNumber' => $companyInfo->getStreetNumber(),
                'streetNumberSuffix' => $companyInfo->getStreetNumberSuffix(),
                'city' => $companyInfo->getCity(),
                'zipCode' => $companyInfo->getZipCode(),
            ],
        ]);

        $this->reload('Údaje byly načteny z ARES.', 'success');
    }

    private function validateInvoiceData(BaseForm $form, ArrayHash $values): void
    {
        $customerContainer = $form['customer'];
        assert($customerContainer instanceof Container);

        $customerValues = $values->customer;
        $customerType = $customerValues->type;

        $requiredFields = [];
        if ($customerType === 'company' || $customerType === 'person') {
            $requiredFields = ['name', 'street', 'city', 'zipCode'];
        }

        if ($customerType === 'company') {
            $requiredFields[] = 'companyNumber';
        }

        foreach ($requiredFields as $field) {
            $control = $customerContainer[$field];
            assert($control instanceof TextInput);

            if (trim((string) $customerValues->{$field}) === '') {
                $control->addError('Pole je povinné.');
            }
        }

        if ($customerType === 'anonymous' && $this->calculateTotalAmount($values)->isGreaterThan(BigDecimal::of('10000'))) {
            $name = $customerContainer['name'];
            assert($name instanceof TextInput);
            $name->addError('Fakturu bez identifikace odběratele lze vystavit pouze do 10 000 Kč.');
        }

        $selectedPaymentType = $values->paymentType ?? $this->defaultPaymentType()->name;
        if (! in_array($selectedPaymentType, array_keys($this->paymentTypeOptions()), true)) {
            $paymentType = $form['paymentType'];
            assert($paymentType instanceof \Nette\Forms\Controls\SelectBox);
            $paymentType->addError('Vybraný způsob platby není pro tuto fakturační řadu dostupný.');
        }
    }

    public function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        $invoiceSupplier = $this->createInvoiceSupplier();
        $invoiceCustomer = InvoiceCustomer::fromForm($values->customer);

        $invoice = Invoice::formForm($values, $this->invoiceSequence, $invoiceSupplier, $invoiceCustomer);
        $invoice->updateEmailRecipients($this->processEmails($values->email));

        foreach ($values['items'] as $item) {
            $invoice->addItem(InvoiceItem::fromForm($item));
        }

        try {
            $this->invoiceManager->create($invoice);
        } catch (VariableSymbolCollision $exception) {
            $this->presenter->flashMessage($exception->getMessage(), 'danger');

            return;
        }

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

    /** @return EmailAddress[] */
    private function processEmails(string $emails): array
    {
        $emails = trim($emails);

        if ($emails === '') {
            return [];
        }

        return array_map(
            fn (string $email) => new EmailAddress($email),
            explode(MyValidators::EMAIL_SEPARATOR, $emails),
        );
    }

    private function calculateTotalAmount(ArrayHash $values): BigDecimal
    {
        $sum = BigDecimal::zero();

        foreach ($values['items'] as $item) {
            $sum = $sum->plus(
                BigDecimal::of((string) $item['price'])->multipliedBy((string) $item['quantity']),
            );
        }

        return $sum;
    }

    private function createInvoiceSupplier(): InvoiceSupplier
    {
        $setting = $this->getInvoiceUnitSetting();

        if ($setting instanceof InvoiceUnitSetting) {
            return $setting->toInvoiceSupplier();
        }

        return InvoiceSupplier::fromOfficialUnit(
            $this->unitRepository->getOfficialUnit($this->invoiceSequence->getUnit()),
            $this->invoiceSequence->getPhone(),
        );
    }

    private function getInvoiceUnitSetting(): ?InvoiceUnitSetting
    {
        $year = $this->invoiceSequence->getYear();

        if ($year === null) {
            return null;
        }

        return $this->invoiceUnitSettings->findByUnitAndYear($this->invoiceSequence->getUnit(), $year);
    }

    /** @return array<string, string> */
    private function paymentTypeOptions(): array
    {
        if ($this->invoiceSequence->getBankAccount() === null) {
            return [
                InvoicePaymentType::CASH->name => InvoicePaymentType::CASH->value,
            ];
        }

        return InvoicePaymentType::toSelect();
    }

    private function defaultPaymentType(): InvoicePaymentType
    {
        return $this->invoiceSequence->getBankAccount() === null
            ? InvoicePaymentType::CASH
            : InvoicePaymentType::TRANSFER;
    }
}
