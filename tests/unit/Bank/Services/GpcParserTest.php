<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Transaction;
use Codeception\Test\Unit;
use DateTimeImmutable;
use InvalidArgumentException;

use function file_get_contents;
use function sprintf;

final class GpcParserTest extends Unit
{
    public function testDecodesCp1250TextValuesToUtf8(): void
    {
        $parser = new GpcParser();
        $generator = new BankTransactionKeyGenerator();
        $contents = (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc');
        $expectedText = json_decode('"\\u017dit\\u00e1 ob\\u011bd Praha 1234"', true);

        self::assertIsString($expectedText);

        $cp1250Text = iconv('UTF-8', 'Windows-1250//IGNORE', $expectedText);

        self::assertNotFalse($cp1250Text);

        $count = 0;
        $contents = str_replace('Najem kancelari 1/26', $cp1250Text, $contents, $count);

        self::assertSame(1, $count);

        $parsedFile = $parser->parseFile(
            '8310192897/2010',
            $contents,
            $generator,
        );
        $transaction = $parsedFile->transactions[0];

        self::assertSame($expectedText, $transaction->getName());
        self::assertSame($expectedText, $transaction->getNote());
    }

    public function testParsesTransactionFromGpcFile(): void
    {
        $parser = new GpcParser();
        $generator = new BankTransactionKeyGenerator();
        $contents = (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc');

        $parsedFile = $parser->parseFile(
            '8310192897/2010',
            $contents,
            $generator,
        );
        $transactions = $parsedFile->transactions;

        self::assertCount(1, $transactions);
        self::assertSame('8310192897', $parsedFile->statementAccountNumber);
        self::assertSame(-24.20, $transactions[0]->getAmount());
        self::assertSame('Najem kancelari 1/26', $transactions[0]->getName());
        self::assertNull($transactions[0]->getVariableSymbol());
        self::assertNull($transactions[0]->getConstantSymbol());
        self::assertSame('2026-02-28', $transactions[0]->getDate()->format('Y-m-d'));
        self::assertStringStartsWith('gpc:', $transactions[0]->getId());
    }

    public function testResolvesStatementAccountNumberFromHeader(): void
    {
        $parser = new GpcParser();

        $statementAccountNumber = $parser->resolveStatementAccountNumber(
            (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc'),
        );

        self::assertSame('8310192897', $statementAccountNumber);
    }

    /**
     * Protiúčet a symboly vyplněné nulami znamenají „nevyplněno". Kdyby z nich vznikla nula nebo
     * naformátovaný řetězec nul, deduplikační klíč by závisel na tom, jak se soubor zpracoval.
     */
    public function testUnfilledCounterAccountAndSymbolsAreNull(): void
    {
        $transaction = (new GpcParser())->parse(
            '8310192897/2010',
            (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc'),
            new BankTransactionKeyGenerator(),
        )[0];

        self::assertNull($transaction->getBankAccount());
        self::assertNull($transaction->getVariableSymbol());
        self::assertNull($transaction->getConstantSymbol());
    }

    public function testDeduplicationKeyIsStableForTheSameFile(): void
    {
        $contents = (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc');
        $parse = static fn (): string => (new GpcParser())->parse(
            '8310192897/2010',
            $contents,
            new BankTransactionKeyGenerator(),
        )[0]->getId();

        self::assertSame($parse(), $parse());
    }

    public function testParsesCounterAccountSymbolsAndCounterPartyNameFromDetailRecord(): void
    {
        $transactions = (new GpcParser())->parse(
            '8310192897/2010',
            (string) file_get_contents(__DIR__.'/../../../_data/bank/reversals-and-details.gpc'),
            new BankTransactionKeyGenerator(),
        );

        self::assertCount(2, $transactions);

        $credit = $transactions[0];
        self::assertSame(100.0, $credit->getAmount());
        self::assertSame('19-17608/2010', $credit->getBankAccount());
        self::assertSame(1234567890, $credit->getVariableSymbol());
        self::assertSame(308, $credit->getConstantSymbol());
        // Jméno protistrany ze záznamu 076 má přednost před 20znakovým textem v 075.
        self::assertSame('Jan Novák', $credit->getName());
        self::assertSame('Jan Novak', $credit->getNote());
        self::assertSame('1000000001', $credit->getSourceTransactionId());
    }

    /**
     * Storno kreditu peníze z účtu odebírá, takže musí mít zápornou částku.
     */
    public function testCreditReversalIsNegative(): void
    {
        $transactions = (new GpcParser())->parse(
            '8310192897/2010',
            (string) file_get_contents(__DIR__.'/../../../_data/bank/reversals-and-details.gpc'),
            new BankTransactionKeyGenerator(),
        );

        self::assertSame(-100.0, $transactions[1]->getAmount());
        self::assertSame('Storno platby', $transactions[1]->getName());
    }

    /**
     * Jeden soubor může obsahovat víc bloků 074; transakce ze všech se musí importovat.
     */
    public function testCollectsTransactionsFromEveryStatementInTheFile(): void
    {
        $transactions = (new GpcParser())->parse(
            '8310192897/2010',
            (string) file_get_contents(__DIR__.'/../../../_data/bank/two-statements.gpc'),
            new BankTransactionKeyGenerator(),
        );

        self::assertCount(2, $transactions);
        self::assertSame(10.0, $transactions[0]->getAmount());
        self::assertSame(-5.0, $transactions[1]->getAmount());
    }

    /**
     * Česká spořitelna čísluje storna 3/4, ostatní banky 4/5. Dialekt se vybírá podle kódu banky
     * účtu, do kterého se importuje — v souboru samotném kód banky není.
     */
    public function testUsesBankDialectDerivedFromTargetAccount(): void
    {
        $contents = (string) file_get_contents(__DIR__.'/../../../_data/bank/cs-reversal.gpc');

        $transactions = (new GpcParser())->parse('123456789/0800', $contents, new BankTransactionKeyGenerator());

        // Kód 3 je u ČS storno debetu, tedy peníze zpět na účet.
        self::assertSame(100.0, $transactions[0]->getAmount());
    }

    public function testUnknownPostingCodeForTheGivenBankIsReported(): void
    {
        $contents = (string) file_get_contents(__DIR__.'/../../../_data/bank/cs-reversal.gpc');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown posting code "3"');

        // Účet u FIO, kde kód 3 neexistuje — soubor patrí jiné bance a nesmí projít tiše.
        (new GpcParser())->parse('123456789/2010', $contents, new BankTransactionKeyGenerator());
    }

    /**
     * Zlatá hodnota odebraná ze skutečné implementace nad ifm24/bank-statements ještě před jejím
     * smazáním. Klíč je identifikátor napříč tabulkami (bank_transaction_pairing, snapshot
     * spárované transakce v pa_payment a invoice), takže existující data se nepřepisují — import
     * musí umět transakci dohledat i pod tímto starým klíčem.
     */
    public function testLegacyKeyMatchesTheKeyProducedByThePreviousImplementation(): void
    {
        $transaction = (new GpcParser())->parse(
            '8310192897/2010',
            (string) file_get_contents(__DIR__.'/../../../_data/bank/sample.gpc'),
            new BankTransactionKeyGenerator(),
        )[0];

        self::assertSame(
            'gpc:0d629eef2403d55f38643fc8da26e685fb5d021e259bee6d0c90734672716906',
            $transaction->getLegacyId(),
        );
        self::assertNotSame($transaction->getId(), $transaction->getLegacyId());
        self::assertSame(
            [$transaction->getId(), $transaction->getLegacyId()],
            $transaction->getKnownIds(),
        );
    }

    /**
     * U protiúčtu, který nová i stará implementace formátovaly stejně, se klíč nemění a legacy klíč
     * je tedy zbytečný.
     */
    public function testKnownIdsHoldASingleKeyWhenNothingChanged(): void
    {
        $transaction = new Transaction(
            'gpc:abc',
            BankTransactionSource::GPC,
            new DateTimeImmutable('2026-02-28'),
            -24.2,
            null,
            'Nekdo',
        );

        self::assertNull($transaction->getLegacyId());
        self::assertSame(['gpc:abc'], $transaction->getKnownIds());
    }

    /**
     * Rekonstrukce nenormalizovaného protiúčtu musí dát stejný klíč jako výpočet ze surové hodnoty,
     * jakou vracela stará knihovna.
     */
    public function testLegacyKeyReconstructsTheUnnormalizedCounterAccount(): void
    {
        $generator = new BankTransactionKeyGenerator();
        $date = new DateTimeImmutable('2026-03-15');

        foreach (
            [
                '19-17608/0100' => '000019-0000017608/0100',
                '8310192897/2010' => '000000-8310192897/2010',
                '112233/0800' => '000000-0000112233/0800',
                null => '000000-0000000000/0000',
            ] as $normalized => $legacyRaw
        ) {
            self::assertSame(
                $generator->fromGpc('1/2010', $date, 100.0, $legacyRaw, 'Nekdo', 1, 2, 'note'),
                $generator->legacyFromGpc('1/2010', $date, 100.0, $normalized === '' ? null : $normalized, 'Nekdo', 1, 2, 'note'),
                sprintf('protiúčet %s', $legacyRaw),
            );
        }
    }
}
