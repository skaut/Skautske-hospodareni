<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Payment\VariableSymbol;
use Codeception\Test\Unit;
use DateTimeImmutable;

final class EmailTemplateTest extends Unit
{
    public function testEvaluateUsesAnonymousCustomerDisplayName(): void
    {
        $template = new EmailTemplate(
            'Faktura %number% pro %customer_name%',
            'VS %vs%, odběratel %customer_name%, částka %amount%',
        );

        $invoice = new Invoice(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', null, null, 14),
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('', '', '', '', '', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            InvoicePaymentType::TRANSFER,
            AccountNumber::fromString('2300228890/2010'),
            'INV00001',
            new VariableSymbol('1'),
        );

        $evaluated = $template->evaluate($invoice, 'Tester');

        self::assertSame('Faktura INV00001 pro Bez identifikace odběratele', $evaluated->getSubject());
        self::assertSame(
            'VS 1, odběratel Bez identifikace odběratele, částka 0',
            $evaluated->getBody(),
        );
    }
}
