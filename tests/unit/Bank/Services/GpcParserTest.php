<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use Codeception\Test\Unit;

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
        $contents = str_replace('Openai *Chatgpt Subs', $cp1250Text, $contents, $count);

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
        self::assertSame('Openai *Chatgpt Subs', $transactions[0]->getName());
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
}
