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
use App\Model\Invoice\Enum\InvoiceState;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Payment\VariableSymbolCollision;
use App\Model\Unit\UnitService;
use App\MyValidators;
use Brick\Math\BigDecimal;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Forms\Container;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Throwable;
use Utility\Ares\ViAresParser;

use function array_map;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_replace;
use function sprintf;
use function trim;

class InvoiceForm extends BaseControl
{
    private int $itemsCount = 0;

    public function __construct(
        private readonly InvoiceSequence $invoiceSequence,
        private readonly InvoiceManager $invoiceManager,
        private readonly UnitService $unitRepository,
        private readonly InvoiceUnitSettingRepository $invoiceUnitSettings,
        private readonly ?Invoice $invoice = null,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/InvoiceForm.latte');
        $this->template->invoiceSequence = $this->invoiceSequence;
        $this->template->invoiceNumberPattern = $this->invoiceSequence->formatInvoiceNumber($this->invoiceSequence->getFirstNumberValue());
        $this->template->variableSymbolPattern = (string) $this->invoiceSequence->generateVariableSymbol($this->invoiceSequence->getFirstNumberValue());
        $this->template->hasBankAccount = $this->invoiceSequence->getBankAccount() !== null;
        $this->template->isEditMode = $this->isEditMode();
        $this->template->submitLabel = $this->isEditMode() ? 'Uložit změny' : 'Vystavit';
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
            ->setDefaultValue($this->editedPaymentType()->name)
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
        if (! $customerType instanceof RadioList) {
            throw new LogicException('Nepodařilo se vytvořit volbu typu odběratele.');
        }
        $customerType->setDefaultValue('company')
            ->setRequired('Vyberte typ odběratele');

        $customerContainer->addText('companyNumber', 'IČO')
            ->addFilter(fn (string $value) => trim((string) preg_replace('/\s+/', '', $value)));
        $customerContainer->addText('vat', 'DIČ');
        $customerContainer->addText('name', 'Název');
        $customerContainer->addText('street', 'Ulice');
        $customerContainer->addText('streetNumber', 'Číslo popisné');
        $customerContainer->addText('streetNumberSuffix', 'Číslo orientační');
        $customerContainer->addText('city', 'Město');
        $customerContainer->addText('zipCode', 'PSČ');

        $loadAres = $form->addSubmit('loadAres', 'Získat z Aresu');
        $loadAres
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-sm btn-light ajax')
            ->setHtmlAttribute('formnovalidate', 'formnovalidate');
        $loadAres->onClick[] = function (SubmitButton $button): void {
            $form = $button->getForm();
            if (! $form instanceof BaseForm) {
                throw new LogicException('ARES lze načíst jen z fakturačního formuláře.');
            }

            $this->loadAresData($form);
        };
        $loadAres->onInvalidClick[] = function (SubmitButton $button): void {
            $form = $button->getForm();
            if (! $form instanceof BaseForm) {
                throw new LogicException('ARES lze načíst jen z fakturačního formuláře.');
            }

            $this->loadAresData($form);
        };

        $itemDefaults = $this->itemDefaults();
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
        }, count($itemDefaults));

        $items->addSubmit('addItem', 'Přidat další položku')
            ->setHtmlAttribute('class', 'btn btn-light ajax')
            ->setValidationScope([])
            ->onClick[] = function () use ($items): void {
                $items->createOne();
                $defaultValues = ['quantity' => 1, 'price' => '0.00', 'unit' => 'ks', 'purpose' => ''];

                $items->setValues($defaultValues);
                $this->reload();
            };

        $form->addSubmit('send', $this->isEditMode() ? 'Uložit změny' : 'Vystavit');
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

        $form->onAnchor[] = function () use ($form, $items): void {
            $this->applyDefaults($form, $items);
        };

        return $form;
    }

    private function removeItem(SubmitButton $button): void
    {
        $container = $button->getParent();
        $replicator = $container->getParent();

        if (! $replicator instanceof \Kdyby\Replicator\Container || ! $container instanceof Container) {
            throw new LogicException('Nepodařilo se odebrat položku faktury.');
        }

        $replicator->remove($container, true);
        $this->reload();
    }

    private function loadAresData(BaseForm $form): void
    {
        $form->cleanErrors();

        $customerContainer = $form->getComponent('customer');
        if (! $customerContainer instanceof Container) {
            throw new LogicException('Kontejner odběratele nebyl nalezen.');
        }

        $values = $customerContainer->getUntrustedValues();
        $companyNumber = trim((string) ($values->companyNumber ?? ''));

        if ($companyNumber === '') {
            $presenter = $this->getPresenter();
            $rawCustomer = $presenter->getHttpRequest()->getPost('customer');
            if (is_array($rawCustomer)) {
                $companyNumber = trim((string) ($rawCustomer['companyNumber'] ?? ''));
            }
        }

        if ($companyNumber === '') {
            $this->redrawAfterAres('Pro načtení z ARES zadejte IČO.', 'warning');

            return;
        }

        try {
            $companyInfo = (new ViAresParser())->getAres($companyNumber);
        } catch (Throwable) {
            $this->redrawAfterAres('Nepodařilo se načíst údaje z ARES.', 'danger');

            return;
        }

        if ($companyInfo->isEmpty()) {
            $this->redrawAfterAres('V ARES nebyly nalezeny údaje pro zadané IČO.', 'warning');

            return;
        }

        $form->setValues([
            'customer' => [
                'type' => 'company',
                'companyNumber' => $companyNumber,
                'vat' => $companyInfo->getVat(),
                'name' => $companyInfo->getName(),
                'street' => $companyInfo->getStreet(),
                'streetNumber' => $companyInfo->getStreetNumber(),
                'streetNumberSuffix' => $companyInfo->getStreetNumberSuffix(),
                'city' => $companyInfo->getCity(),
                'zipCode' => $companyInfo->getZipCode(),
            ],
        ]);

        $this->redrawAfterAres('Údaje byly načteny z ARES.', 'success');
    }

    private function redrawAfterAres(string $message, string $type): void
    {
        $this->flashMessage($message, $type);

        $presenter = $this->getPresenter();
        if ($presenter->isAjax()) {
            $this->redrawControl();
        }
    }

    private function validateInvoiceData(BaseForm $form, ArrayHash $values): void
    {
        $customerContainer = $form['customer'];
        if (! $customerContainer instanceof Container) {
            throw new LogicException('Kontejner odběratele nebyl nalezen.');
        }

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
            if (! $control instanceof TextInput) {
                throw new LogicException('Očekává se textové pole odběratele.');
            }

            if (trim((string) $customerValues->{$field}) === '') {
                $control->addError('Pole je povinné.');
            }
        }

        if ($customerType === 'anonymous' && $this->calculateTotalAmount($values)->isGreaterThan(BigDecimal::of('10000'))) {
            $name = $customerContainer['name'];
            if (! $name instanceof TextInput) {
                throw new LogicException('Pole názvu odběratele nebylo nalezeno.');
            }

            $name->addError('Fakturu bez identifikace odběratele lze vystavit pouze do 10 000 Kč.');
        }

        $selectedPaymentType = $values->paymentType ?? $this->defaultPaymentType()->name;
        if (! in_array($selectedPaymentType, array_keys($this->paymentTypeOptions()), true)) {
            $paymentType = $form['paymentType'];
            if (! $paymentType instanceof SelectBox) {
                throw new LogicException('Pole způsobu platby nebylo nalezeno.');
            }

            $paymentType->addError('Vybraný způsob platby není pro tuto fakturační řadu dostupný.');
        }
    }

    public function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        try {
            if ($this->isEditMode()) {
                $this->updateInvoice($values);
            } else {
                $this->createInvoice($values);
            }
        } catch (VariableSymbolCollision $exception) {
            $this->presenter->flashMessage($exception->getMessage(), 'danger');

            return;
        }

        $message = $this->isEditMode() ? 'Faktura byla upravena' : 'Faktura byla vytvořena';
        $this->presenter->flashMessage($message);
        if ($this->isEditMode()) {
            if ($this->invoice === null) {
                throw new LogicException('Upravovaná faktura nebyla nalezena.');
            }

            $this->presenter->redirect(':Payments:InvoiceList:default', [
                'invoiceSequenceId' => $this->invoiceSequence->getId(),
                'unitId' => $this->invoiceSequence->getUnit(),
            ]);

            return;
        }

        if ($this->presenter->isAjax()) {
            $formComponent = $this->getComponent('form');
            if (! $formComponent instanceof BaseForm) {
                throw new LogicException('Formulář faktury nebyl nalezen.');
            }

            $formComponent->setValues([], true);
            $this->presenter->redrawControl('form');
        } else {
            $this->presenter->redirect(':Payments:InvoiceList:default', [
                'invoiceSequenceId' => $this->invoiceSequence->getId(),
                'unitId' => $this->invoiceSequence->getUnit(),
            ]);
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

    private function isEditMode(): bool
    {
        return $this->invoice instanceof Invoice;
    }

    private function editedPaymentType(): InvoicePaymentType
    {
        return $this->invoice?->getPaymentType() ?? $this->defaultPaymentType();
    }

    private function applyDefaults(BaseForm $form, \Kdyby\Replicator\Container $items): void
    {
        if (! $this->isEditMode()) {
            return;
        }

        if ($this->invoice === null) {
            throw new LogicException('Upravovaná faktura nebyla nalezena.');
        }

        $customer = $this->invoice->getCustomer();
        $address = $customer->getAddress();

        $dueDate = $form['dueDate'];
        if (! $dueDate instanceof \Component\Forms\DateControl) {
            throw new LogicException('Pole data splatnosti nebylo nalezeno.');
        }

        $dateOfIssue = $form['dateOfIssue'];
        if (! $dateOfIssue instanceof \Component\Forms\DateControl) {
            throw new LogicException('Pole data vystavení nebylo nalezeno.');
        }

        $dueDate->setDefaultValue(new ChronosDate($this->invoice->getDueDate()));
        $dateOfIssue->setDefaultValue(new ChronosDate($this->invoice->getDateOfIssue()));
        $issuedBy = $form['issuedBy'];
        if (! $issuedBy instanceof TextInput) {
            throw new LogicException('Pole vystavil nebylo nalezeno.');
        }

        $paymentType = $form['paymentType'];
        if (! $paymentType instanceof SelectBox) {
            throw new LogicException('Pole způsobu platby nebylo nalezeno.');
        }

        $email = $form['email'];
        if (! $email instanceof TextInput) {
            throw new LogicException('Pole e-mailu příjemce nebylo nalezeno.');
        }

        $issuedBy->setDefaultValue($this->invoice->getIssuedBy());
        $paymentType->setDefaultValue($this->invoice->getPaymentType()->name);
        $email->setDefaultValue(implode(
            MyValidators::EMAIL_SEPARATOR,
            array_map(
                static fn (EmailAddress $emailAddress): string => $emailAddress->getValue(),
                $this->invoice->getEmailRecipients(),
            ),
        ));

        $customerContainer = $form['customer'];
        if (! $customerContainer instanceof Container) {
            throw new LogicException('Kontejner odběratele nebyl nalezen.');
        }

        $this->setTextDefault($customerContainer, 'companyNumber', $customer->getCompanyNumber());
        $this->setTextDefault($customerContainer, 'vat', $customer->getVatNumber());
        $this->setTextDefault($customerContainer, 'name', $customer->getName());
        $this->setTextDefault($customerContainer, 'street', $address->getStreet());
        $this->setTextDefault($customerContainer, 'streetNumber', (string) $address->getStreetNumber());
        $this->setTextDefault($customerContainer, 'streetNumberSuffix', (string) $address->getStreetNumberSuffix());
        $this->setTextDefault($customerContainer, 'city', $address->getCity());
        $this->setTextDefault($customerContainer, 'zipCode', $address->getZipCode());

        $customerType = $customerContainer['type'];
        if (! $customerType instanceof RadioList) {
            throw new LogicException('Pole typu odběratele nebylo nalezeno.');
        }

        $customerType->setDefaultValue($this->customerType());

        foreach ($this->itemDefaults() as $index => $itemDefault) {
            $itemContainer = $items->getComponent((string) $index);
            if (! $itemContainer instanceof Container) {
                throw new LogicException('Kontejner položky faktury nebyl nalezen.');
            }

            foreach ($itemDefault as $field => $value) {
                $this->setItemDefault($itemContainer, $field, $value);
            }
        }
    }

    private function setTextDefault(Container $container, string $name, string $value): void
    {
        $control = $container[$name];
        if (! $control instanceof TextInput) {
            throw new LogicException(sprintf('Pole "%s" nebylo nalezeno.', $name));
        }

        $control->setDefaultValue($value);
    }

    private function setItemDefault(Container $container, string $name, int|string $value): void
    {
        $control = $container[$name];
        if (! $control instanceof TextInput) {
            throw new LogicException(sprintf('Pole položky "%s" nebylo nalezeno.', $name));
        }

        $control->setDefaultValue($value);
    }

    /**
     * @return list<array{purpose: string, quantity: int, unit: string, price: string}>
     */
    private function itemDefaults(): array
    {
        if (! $this->isEditMode()) {
            return [[
                'purpose' => '',
                'quantity' => 1,
                'unit' => 'ks',
                'price' => '0.00',
            ]];
        }

        if ($this->invoice === null) {
            throw new LogicException('Upravovaná faktura nebyla nalezena.');
        }

        return array_map(
            static fn (InvoiceItem $item): array => [
                'purpose' => $item->getPurpose(),
                'quantity' => $item->getQuantity(),
                'unit' => $item->getUnit(),
                'price' => (string) $item->getPrice(),
            ],
            $this->invoice->getItems()->toArray(),
        );
    }

    private function customerType(): string
    {
        if ($this->invoice === null) {
            return 'company';
        }

        $customer = $this->invoice->getCustomer();
        if ($customer->isAnonymous()) {
            return 'anonymous';
        }

        return $customer->hasCompanyNumber() ? 'company' : 'person';
    }

    private function createInvoice(ArrayHash $values): void
    {
        $invoice = $this->buildNewInvoice($values);
        $this->invoiceManager->create($invoice);
    }

    private function buildNewInvoice(ArrayHash $values): Invoice
    {
        $invoiceSupplier = $this->createInvoiceSupplier();
        $invoiceCustomer = InvoiceCustomer::fromForm($values->customer);

        $invoice = Invoice::formForm($values, $this->invoiceSequence, $invoiceSupplier, $invoiceCustomer);
        $invoice->updateEmailRecipients($this->processEmails($values->email));

        foreach ($values['items'] as $item) {
            $invoice->addItem(InvoiceItem::fromForm($item));
        }

        return $invoice;
    }

    private function updateInvoice(ArrayHash $values): void
    {
        if ($this->invoice === null) {
            throw new LogicException('Upravovaná faktura nebyla nalezena.');
        }

        if ($this->invoice->getState() !== InvoiceState::ISSUED) {
            throw new LogicException('Upravit lze pouze fakturu ve stavu Vystavená.');
        }

        $this->invoice->setSupplier($this->createInvoiceSupplier());
        $this->invoice->setCustomer(InvoiceCustomer::fromForm($values->customer));
        $this->invoice->setIssuedBy((string) $values->issuedBy);
        $this->invoice->setDueDate($values->dueDate);
        $this->invoice->setDateOfIssue($values->dateOfIssue);
        $this->invoice->setDateOfTaxPayment($values->dateOfIssue);
        $paymentType = constant(InvoicePaymentType::class.'::'.$values->paymentType);
        if (! $paymentType instanceof InvoicePaymentType) {
            throw new LogicException('Vybraný způsob platby není platný.');
        }

        $this->invoice->setPaymentType($paymentType);
        $this->invoice->setBankAccount($this->invoiceSequence->getBankAccount());
        $this->invoice->setAccountNumber($this->invoiceSequence->getBankAccount()?->getNumber());
        $this->invoice->setBankName($this->invoiceSequence->getBankAccount()?->getNumber()->getBankName());
        $this->invoice->setIban($this->invoiceSequence->getBankAccount()?->getNumber()->getIban());
        $this->invoice->setBic($this->invoiceSequence->getBankAccount()?->getNumber()->getBic());
        $this->invoice->updateEmailRecipients($this->processEmails((string) $values->email));

        foreach ($this->invoice->getItems()->toArray() as $item) {
            $this->invoice->removeItem($item);
        }

        foreach ($values['items'] as $item) {
            $this->invoice->addItem(InvoiceItem::fromForm($item));
        }

        $this->invoiceManager->update($this->invoice);
    }
}
