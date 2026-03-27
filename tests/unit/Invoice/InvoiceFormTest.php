<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Payment\IUnitResolver;
use App\Model\Unit\UnitService;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Mockery;
use Nette\Forms\Container;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\ArrayHash;
use ReflectionMethod;
use ReflectionProperty;

final class InvoiceFormTest extends Unit
{
    private InvoiceForm $component;

    public function testCustomerFieldsRequiredForInvoiceDocument(): void
    {
        $form = $this->createForm();
        /** @var Container $customer */
        $customer = $form->getComponent('customer');

        $type = $customer['type'];
        $companyNumber = $customer['companyNumber'];
        $name = $customer['name'];
        $street = $customer['street'];
        $city = $customer['city'];
        $zipCode = $customer['zipCode'];
        $vat = $customer['vat'];

        self::assertInstanceOf(RadioList::class, $type);
        self::assertTrue($type->isRequired());
        self::assertInstanceOf(TextInput::class, $companyNumber);
        self::assertFalse($companyNumber->isRequired());
        self::assertInstanceOf(TextInput::class, $name);
        self::assertFalse($name->isRequired());
        self::assertInstanceOf(TextInput::class, $street);
        self::assertFalse($street->isRequired());
        self::assertInstanceOf(TextInput::class, $city);
        self::assertFalse($city->isRequired());
        self::assertInstanceOf(TextInput::class, $zipCode);
        self::assertFalse($zipCode->isRequired());
        self::assertInstanceOf(TextInput::class, $vat);
        self::assertFalse($vat->isRequired());
    }

    public function testAnonymousInvoiceUpToTenThousandDoesNotRequireCustomerIdentification(): void
    {
        $form = $this->createForm();
        $this->validate($form, ArrayHash::from([
            'customer' => [
                'type' => 'anonymous',
                'companyNumber' => '',
                'name' => '',
                'street' => '',
                'city' => '',
                'zipCode' => '',
            ],
            'items' => [
                ['price' => '10000.00', 'quantity' => 1],
            ],
        ], true));

        /** @var Container $customer */
        $customer = $form->getComponent('customer');
        $name = $customer['name'];
        self::assertInstanceOf(TextInput::class, $name);
        self::assertCount(0, $name->getErrors());
    }

    public function testAnonymousInvoiceAboveTenThousandIsRejected(): void
    {
        $form = $this->createForm();
        $this->validate($form, ArrayHash::from([
            'customer' => [
                'type' => 'anonymous',
                'companyNumber' => '',
                'name' => '',
                'street' => '',
                'city' => '',
                'zipCode' => '',
            ],
            'items' => [
                ['price' => '10000.01', 'quantity' => 1],
            ],
        ], true));

        /** @var Container $customer */
        $customer = $form->getComponent('customer');
        $name = $customer['name'];
        self::assertInstanceOf(TextInput::class, $name);
        self::assertSame(
            ['Fakturu bez identifikace odběratele lze vystavit pouze do 10 000 Kč.'],
            $name->getErrors(),
        );
    }

    public function testCompanyInvoiceRequiresCompanyNumber(): void
    {
        $form = $this->createForm();
        $this->validate($form, ArrayHash::from([
            'customer' => [
                'type' => 'company',
                'companyNumber' => '',
                'name' => 'Skaut s.r.o.',
                'street' => 'Krizikova',
                'city' => 'Praha',
                'zipCode' => '18600',
            ],
            'items' => [
                ['price' => '100.00', 'quantity' => 1],
            ],
        ], true));

        /** @var Container $customer */
        $customer = $form->getComponent('customer');
        $companyNumber = $customer['companyNumber'];
        self::assertInstanceOf(TextInput::class, $companyNumber);
        self::assertSame(['Pole je povinné.'], $companyNumber->getErrors());
    }

    public function testPersonInvoiceDoesNotRequireCompanyNumber(): void
    {
        $form = $this->createForm();
        $this->validate($form, ArrayHash::from([
            'customer' => [
                'type' => 'person',
                'companyNumber' => '',
                'name' => 'Jan Novak',
                'street' => 'Krizikova',
                'city' => 'Praha',
                'zipCode' => '18600',
            ],
            'items' => [
                ['price' => '100.00', 'quantity' => 1],
            ],
        ], true));

        /** @var Container $customer */
        $customer = $form->getComponent('customer');
        $companyNumber = $customer['companyNumber'];
        $name = $customer['name'];
        self::assertInstanceOf(TextInput::class, $companyNumber);
        self::assertInstanceOf(TextInput::class, $name);
        self::assertCount(0, $companyNumber->getErrors());
        self::assertCount(0, $name->getErrors());
    }

    public function testInvoiceWithoutBankAccountAllowsOnlyCashPayment(): void
    {
        $form = $this->createForm();
        $paymentType = $form['paymentType'];

        self::assertInstanceOf(SelectBox::class, $paymentType);
        self::assertSame(
            [InvoicePaymentType::CASH->name => InvoicePaymentType::CASH->value],
            $paymentType->getItems(),
        );
        self::assertSame(InvoicePaymentType::CASH->name, $paymentType->getValue());
    }

    public function testInvoiceWithBankAccountDefaultsToTransfer(): void
    {
        $form = $this->createForm($this->createBankAccount());
        $paymentType = $form['paymentType'];

        self::assertInstanceOf(SelectBox::class, $paymentType);
        self::assertSame(InvoicePaymentType::TRANSFER->name, $paymentType->getValue());
        self::assertSame(
            InvoicePaymentType::toSelect(),
            $paymentType->getItems(),
        );
    }

    public function testInvoiceRecipientEmailIsOptional(): void
    {
        $form = $this->createForm();
        $email = $form['email'];

        self::assertInstanceOf(TextInput::class, $email);
        self::assertFalse($email->isRequired());
        self::assertSame(
            'Nepovinné. Pokud není vyplněn, fakturu nebude možné odeslat e-mailem.',
            $email->getOption('description'),
        );
    }

    public function testEmptyRecipientEmailProducesNoRecipients(): void
    {
        $this->createForm();

        $method = new ReflectionMethod(InvoiceForm::class, 'processEmails');
        $method->setAccessible(true);

        $result = $method->invoke($this->component, '');

        self::assertSame([], $result);
    }

    public function testRecipientEmailListIsParsedIntoRecipients(): void
    {
        $this->createForm();

        $method = new ReflectionMethod(InvoiceForm::class, 'processEmails');
        $method->setAccessible(true);

        /** @var list<EmailAddress> $result */
        $result = $method->invoke($this->component, 'first@example.test,second@example.test');

        self::assertCount(2, $result);
        self::assertSame('first@example.test', $result[0]->getValue());
        self::assertSame('second@example.test', $result[1]->getValue());
    }

    public function testTransferPaymentIsRejectedWhenSequenceHasNoBankAccount(): void
    {
        $form = $this->createForm();
        $this->validate($form, ArrayHash::from([
            'paymentType' => InvoicePaymentType::TRANSFER->name,
            'customer' => [
                'type' => 'person',
                'companyNumber' => '',
                'name' => 'Jan Novak',
                'street' => 'Krizikova',
                'city' => 'Praha',
                'zipCode' => '18600',
            ],
            'items' => [
                ['price' => '100.00', 'quantity' => 1],
            ],
        ], true));

        $paymentType = $form['paymentType'];
        self::assertInstanceOf(SelectBox::class, $paymentType);
        self::assertSame(
            ['Vybraný způsob platby není pro tuto fakturační řadu dostupný.'],
            $paymentType->getErrors(),
        );
    }

    public function testAresSubmitSkipsInvoiceValidation(): void
    {
        $form = $this->createForm();

        /** @var Container $customer */
        $customer = $form->getComponent('customer');
        $ares = $customer['ares'];
        $name = $customer['name'];

        self::assertInstanceOf(SubmitButton::class, $ares);
        self::assertInstanceOf(TextInput::class, $name);

        $httpData = new ReflectionProperty(\Nette\Forms\Form::class, 'httpData');
        $httpData->setAccessible(true);
        $httpData->setValue($form, []);
        $form->setSubmittedBy($ares);
        foreach ($form->onValidate as $handler) {
            $handler($form, ArrayHash::from([
                'customer' => [
                    'type' => 'company',
                    'companyNumber' => '27074358',
                    'name' => '',
                    'street' => '',
                    'city' => '',
                    'zipCode' => '',
                ],
                'items' => [
                    ['price' => '100.00', 'quantity' => 1],
                ],
            ], true));
        }

        self::assertSame([], $name->getErrors());
    }

    private function createForm(?BankAccount $bankAccount = null): \Component\Forms\BaseForm
    {
        $this->component = new InvoiceForm(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', $bankAccount, null, 14),
            Mockery::mock(InvoiceManager::class),
            Mockery::mock(UnitService::class),
            Mockery::mock(InvoiceUnitSettingRepository::class),
        );

        $method = new ReflectionMethod($this->component, 'createComponentForm');

        /** @var \Component\Forms\BaseForm $form */
        $form = $method->invoke($this->component);

        return $form;
    }

    private function validate(\Component\Forms\BaseForm $form, ArrayHash $values): void
    {
        $method = new ReflectionMethod(InvoiceForm::class, 'validateInvoiceData');
        $method->setAccessible(true);
        $method->invoke($this->component, $form, $values);
    }

    private function createBankAccount(): BankAccount
    {
        return new BankAccount(
            123,
            'Fio',
            AccountNumber::fromString('2300228890/2010'),
            null,
            new DateTimeImmutable('2026-03-11 10:00:00'),
            Mockery::mock(IUnitResolver::class, [
                'getOfficialUnitId' => 123,
            ]),
        );
    }
}
