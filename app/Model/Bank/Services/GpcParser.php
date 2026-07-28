<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Transaction;
use DateTimeImmutable;
use InvalidArgumentException;
use Webwings\BankStatements\Abo\AboDialect;
use Webwings\BankStatements\Abo\AboParser;
use Webwings\BankStatements\Abo\Line;
use Webwings\BankStatements\Abo\RecordType;
use Webwings\BankStatements\AccountNumber;
use Webwings\BankStatements\Exception\BankStatementsException;
use Webwings\BankStatements\StatementList;
use Webwings\BankStatements\Transaction as StatementTransaction;

use function array_map;
use function explode;
use function preg_split;
use function sprintf;
use function trim;

/**
 * Převádí bankovní výpis ve formátu ABO/GPC na doménové transakce.
 *
 * Samotné čtení formátu řeší webwingscz/bank-statements; tato služba jen vybírá bankovní dialekt
 * podle účtu, do kterého se importuje, a mapuje výsledek na {@see Transaction} včetně
 * deduplikačního klíče.
 */
final class GpcParser
{
    public function parseFile(string $accountNumber, string $contents, BankTransactionKeyGenerator $keyGenerator): ParsedGpcFile
    {
        $bankCode = $this->resolveBankCode($accountNumber);
        $statements = $this->parseStatements($contents, $bankCode);

        // Bez kódu banky — ten se doplňuje až do efektivního čísla účtu níže a volající s ním
        // porovnává účet, do kterého se importuje.
        $statementAccountNumber = $statements->first()?->accountNumber?->withBankCode(null)->toString();
        $effectiveAccountNumber = $statementAccountNumber !== null
            ? $this->appendBankCode($statementAccountNumber, $bankCode)
            : $accountNumber;

        return new ParsedGpcFile(
            $statementAccountNumber,
            array_map(
                fn (StatementTransaction $transaction): Transaction => $this->mapTransaction(
                    $effectiveAccountNumber,
                    $transaction,
                    $keyGenerator,
                ),
                $statements->transactions(),
            ),
        );
    }

    /** @return list<Transaction> */
    public function parse(string $accountNumber, string $contents, BankTransactionKeyGenerator $keyGenerator): array
    {
        return $this->parseFile($accountNumber, $contents, $keyGenerator)->transactions;
    }

    /**
     * Číslo účtu z hlavičky výpisu (záznam 074) ve tvaru `předčíslí-číslo`, bez kódu banky.
     *
     * Čte se jen hlavička, aby zjištění účtu nezáviselo na tom, jestli zbytek souboru odpovídá
     * očekávanému dialektu.
     */
    public function resolveStatementAccountNumber(string $contents): ?string
    {
        $rawLines = preg_split('~\R~', $contents);

        foreach ($rawLines === false ? [] : $rawLines as $index => $raw) {
            $line = new Line($raw, $index + 1);

            if ($line->type() !== RecordType::StatementHeader) {
                continue;
            }

            return AccountNumber::fromAboField($line->field(4, 16))?->toString();
        }

        return null;
    }

    private function parseStatements(string $contents, ?string $bankCode): StatementList
    {
        try {
            return (new AboParser(AboDialect::forBankCode($bankCode)))->parseString($contents);
        } catch (BankStatementsException $exception) {
            throw new InvalidArgumentException(sprintf('GPC soubor se nepodarilo zpracovat: %s', $exception->getMessage()), 0, $exception);
        }
    }

    private function mapTransaction(
        string $accountNumber,
        StatementTransaction $transaction,
        BankTransactionKeyGenerator $keyGenerator,
    ): Transaction {
        $date = $transaction->date() ?? new DateTimeImmutable();
        $counterAccount = $transaction->counterAccount?->toString();
        $name = $transaction->payerOrPayeeName() ?? $transaction->documentId ?? '';
        $amount = $transaction->amount->toFloat();
        $variableSymbol = $this->toInt($transaction->variableSymbol);
        $constantSymbol = $this->toInt($transaction->constantSymbol);

        return new Transaction(
            $keyGenerator->fromGpc($accountNumber, $date, $amount, $counterAccount, $name, $variableSymbol, $constantSymbol, $transaction->note),
            BankTransactionSource::GPC,
            $date,
            $amount,
            $counterAccount,
            $name,
            $variableSymbol,
            $constantSymbol,
            $transaction->note,
            $transaction->documentId,
            // Klíč z implementace nad ifm24 — díky němu import pozná transakci, která už je
            // v databázi z doby před přechodem na webwingscz/bank-statements.
            $keyGenerator->legacyFromGpc($accountNumber, $date, $amount, $counterAccount, $name, $variableSymbol, $constantSymbol, $transaction->note),
        );
    }

    private function toInt(?string $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function appendBankCode(string $accountNumber, ?string $bankCode): string
    {
        if ($bankCode === null || $bankCode === '') {
            return $accountNumber;
        }

        return $accountNumber.'/'.$bankCode;
    }

    private function resolveBankCode(string $accountNumber): ?string
    {
        $parts = explode('/', $accountNumber, 2);
        $bankCode = isset($parts[1]) ? trim($parts[1]) : '';

        return $bankCode === '' ? null : $bankCode;
    }
}
