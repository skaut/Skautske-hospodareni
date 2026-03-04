<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Model\Payment\Commands\Payment\CreatePayment;
use Nette\Schema\ValidationException;

final class CsvParserTest extends Unit
{
    public function testParsesValidCsvLine(): void
    {
        $payments = (new CsvParser())->parse(
            123,
            "Alice,150.5,4.3.2026,\"alice@example.com,bob@example.com\",123456,308,poznamka",
        );

        self::assertCount(1, $payments);
        self::assertContainsOnlyInstancesOf(CreatePayment::class, $payments);

        $payment = $payments[0];
        self::assertSame(123, $payment->getGroupId());
        self::assertSame('Alice', $payment->getName());
        self::assertSame(150.5, $payment->getAmount());
        self::assertTrue($payment->getDueDate()->equals(ChronosDate::create(2026, 3, 4)));
        self::assertSame(['alice@example.com', 'bob@example.com'], array_map('strval', $payment->getRecipients()));
        self::assertSame('123456', $payment->getVariableSymbol()?->__toString());
        self::assertSame(308, $payment->getConstantSymbol());
        self::assertSame('poznamka', $payment->getNote());
    }

    public function testParsesCsvLineWithEmptyOptionalValues(): void
    {
        $payments = (new CsvParser())->parse(
            456,
            "Bob,99.9,04.03.2026,,,,",
        );

        self::assertCount(1, $payments);

        $payment = $payments[0];
        self::assertSame([], $payment->getRecipients());
        self::assertNull($payment->getVariableSymbol());
        self::assertNull($payment->getConstantSymbol());
        self::assertSame('', $payment->getNote());
    }

    public function testRejectsInvalidEmailAddress(): void
    {
        $this->expectException(ValidationException::class);

        (new CsvParser())->parse(
            123,
            "Alice,150,4.3.2026,\"not-an-email\",,,",
        );
    }
}
