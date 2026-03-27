<?php

declare(strict_types=1);

namespace Entity;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Invoice\Entity\InvoiceSequence;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;

final class InvoiceSequenceTest extends Unit
{
    public function testFormatsInvoiceNumberWithPrefixAndConfiguredWidth(): void
    {
        $sequence = new InvoiceSequence(123, 'FAO93', 2026, 'Hlavni rada', null, null, 14, '00001');

        self::assertSame('FAO9300001', $sequence->formatInvoiceNumber(1));
        self::assertSame('FAO9300042', $sequence->formatInvoiceNumber(42));
    }

    public function testGeneratesVariableSymbolFromNumericPartOfPrefixAndNumber(): void
    {
        $sequence = new InvoiceSequence(123, 'FAO93', 2026, 'Hlavni rada', null, null, 14, '00001');

        self::assertSame('9300001', (string) $sequence->generateVariableSymbol(1));
    }

    public function testGeneratesVariableSymbolFromNumberOnlyWhenPrefixHasNoDigits(): void
    {
        $sequence = new InvoiceSequence(123, 'FAO', 2026, 'Hlavni rada', null, null, 14, '00001');

        self::assertSame('1', (string) $sequence->generateVariableSymbol(1));
        self::assertSame('42', (string) $sequence->generateVariableSymbol(42));
    }

    public function testChangingBankAccountInvalidatesLastPairing(): void
    {
        $unitResolver = new class implements \App\Model\Payment\IUnitResolver {
            public function getOfficialUnitId(int $unitId): int
            {
                return $unitId;
            }
        };

        $firstAccount = new BankAccount(123, 'Prvni', Helpers::createAccountNumber(), null, new DateTimeImmutable(), $unitResolver);
        $secondAccount = new BankAccount(123, 'Druhy', Helpers::createAccountNumber(), null, new DateTimeImmutable(), $unitResolver);
        Helpers::assignIdentity($firstAccount, 1);
        Helpers::assignIdentity($secondAccount, 2);

        $sequence = new InvoiceSequence(123, 'FAO', 2026, 'Hlavni rada', $firstAccount, null, 14, '00001');
        $sequence->updateLastPairing(new DateTimeImmutable('2026-03-14 10:00:00'));

        $sequence->setBankAccount($secondAccount);

        self::assertSame($secondAccount, $sequence->getBankAccount());
        self::assertNull($sequence->getLastPairing());
    }
}
