<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use Codeception\Test\Unit;
use InvalidArgumentException;

use function file_get_contents;

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
}
