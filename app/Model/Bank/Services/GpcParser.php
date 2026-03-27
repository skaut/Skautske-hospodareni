<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Transaction;
use DateTimeImmutable;
use JakubZapletal\Component\BankStatement\Parser\ABOParser;
use JakubZapletal\Component\BankStatement\Statement\Transaction\AdditionalInformationInterface;
use JakubZapletal\Component\BankStatement\Statement\Transaction\TransactionInterface;
use RuntimeException;
use Throwable;

use function array_map;
use function array_values;
use function error_reporting;
use function explode;
use function fclose;
use function fflush;
use function fwrite;
use function iconv;
use function is_resource;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_split;
use function rtrim;
use function str_starts_with;
use function stream_get_meta_data;
use function strlen;
use function substr;
use function tmpfile;
use function trim;

final class GpcParser
{
    public function parseFile(string $accountNumber, string $contents, BankTransactionKeyGenerator $keyGenerator): ParsedGpcFile
    {
        $statementAccountNumber = $this->resolveStatementAccountNumber($contents);
        $effectiveAccountNumber = $statementAccountNumber !== null
            ? $this->appendBankCode($statementAccountNumber, $this->resolveBankCode($accountNumber))
            : $accountNumber;

        return new ParsedGpcFile(
            $statementAccountNumber,
            $this->parseTransactions($effectiveAccountNumber, $contents, $keyGenerator),
        );
    }

    /** @return list<Transaction> */
    public function parse(string $accountNumber, string $contents, BankTransactionKeyGenerator $keyGenerator): array
    {
        return $this->parseFile($accountNumber, $contents, $keyGenerator)->transactions;
    }

    /** @return list<Transaction> */
    private function parseTransactions(string $accountNumber, string $contents, BankTransactionKeyGenerator $keyGenerator): array
    {
        $libraryFailure = null;

        try {
            $statement = $this->parseStatement($contents);
            $transactions = array_values(array_map(
                fn (TransactionInterface $transaction): Transaction => $this->mapTransaction($accountNumber, $transaction, $keyGenerator),
                $statement->getTransactions(),
            ));

            if ($transactions !== [] || trim($contents) === '') {
                return $transactions;
            }
        } catch (Throwable $exception) {
            $libraryFailure = $exception;
        }

        $fallbackTransactions = $this->parseFallback($accountNumber, $contents, $keyGenerator);

        if ($fallbackTransactions === [] && $libraryFailure !== null) {
            throw new RuntimeException('Nepodarilo se zpracovat GPC soubor pomoci knihovny ani lokalniho fallback parseru.', 0, $libraryFailure);
        }

        return $fallbackTransactions;
    }

    public function resolveStatementAccountNumber(string $contents): ?string
    {
        foreach (preg_split('~\R~u', $contents) ?: [] as $line) {
            if (! str_starts_with($line, '074')) {
                continue;
            }

            return $this->normalizeStatementAccountNumber(substr($line, 3, 16));
        }

        return null;
    }

    private function mapTransaction(
        string $accountNumber,
        TransactionInterface $transaction,
        BankTransactionKeyGenerator $keyGenerator,
    ): Transaction {
        $date = $transaction->getDateCreated() ?? new DateTimeImmutable();
        $counterAccount = $this->normalizeString($transaction->getCounterAccountNumber());
        $note = $this->normalizeString($transaction->getNote());
        $receiptId = $this->normalizeString($transaction->getReceiptId());
        $name = $this->resolveName($transaction->getAdditionalInformation(), $note, $receiptId);
        $credit = (float) $transaction->getCredit();
        $debit = (float) $transaction->getDebit();
        $amount = $credit !== 0.0
            ? $credit
            : -$debit;
        $variableSymbol = $this->normalizeInt($transaction->getVariableSymbol());
        $constantSymbol = $this->normalizeInt($transaction->getConstantSymbol());

        return new Transaction(
            $keyGenerator->fromGpc($accountNumber, $date, $amount, $counterAccount, $name, $variableSymbol, $constantSymbol, $note),
            BankTransactionSource::GPC,
            $date,
            $amount,
            $counterAccount,
            $name,
            $variableSymbol,
            $constantSymbol,
            $note,
            $receiptId,
        );
    }

    private function resolveName(
        ?AdditionalInformationInterface $additionalInformation,
        ?string $note,
        ?string $receiptId,
    ): string {
        $counterPartyName = $additionalInformation !== null
            ? $this->normalizeString($additionalInformation->getCounterPartyName())
            : null;

        return $counterPartyName
            ?? $note
            ?? $receiptId
            ?? '';
    }

    private function normalizeString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $this->decodeTextValue($value);
    }

    private function normalizeInt(int|string|null $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' || $value === '0'
            ? null
            : (int) $value;
    }

    private function parseStatement(string $contents): \JakubZapletal\Component\BankStatement\Statement\Statement
    {
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        $temporaryFile = tmpfile();

        try {
            if ($temporaryFile === false) {
                throw new RuntimeException('Nepodarilo se vytvorit docasny soubor pro GPC import.');
            }

            $writtenBytes = fwrite($temporaryFile, $contents);

            if ($writtenBytes === false || $writtenBytes !== strlen($contents)) {
                throw new RuntimeException('Nepodarilo se zapsat obsah GPC souboru do docasneho souboru.');
            }

            fflush($temporaryFile);
            $metadata = stream_get_meta_data($temporaryFile);
            $temporaryFilePath = $metadata['uri'] ?? null;

            if (! is_string($temporaryFilePath) || $temporaryFilePath === '') {
                throw new RuntimeException('Nepodarilo se zjistit cestu k docasnemu GPC souboru.');
            }

            return (new ABOParser())->parseFile($temporaryFilePath);
        } finally {
            if (is_resource($temporaryFile)) {
                fclose($temporaryFile);
            }

            error_reporting($previousErrorReporting);
        }
    }

    /** @return list<Transaction> */
    private function parseFallback(string $accountNumber, string $contents, BankTransactionKeyGenerator $keyGenerator): array
    {
        $transactions = [];
        $currentIndex = null;

        foreach (preg_split('~\R~u', $contents) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (str_starts_with($line, '075')) {
                $transactions[] = $this->parseFallbackTransactionLine($accountNumber, $line, $keyGenerator);
                $currentIndex = array_key_last($transactions);

                continue;
            }

            if (str_starts_with($line, '076') && $currentIndex !== null) {
                $transactions[$currentIndex] = $this->applyFallbackAdditionalInformation($transactions[$currentIndex], $line, $accountNumber, $keyGenerator);
            }
        }

        return array_values($transactions);
    }

    private function parseFallbackTransactionLine(
        string $accountNumber,
        string $line,
        BankTransactionKeyGenerator $keyGenerator,
    ): Transaction {
        $receiptId = $this->normalizeString(ltrim(substr($line, 35, 13), '0'));
        $amount = $this->resolveFallbackAmount($line, $accountNumber);
        $counterAccount = $this->normalizeCounterAccount(substr($line, 19, 6), substr($line, 25, 10), substr($line, 73, 4));
        $note = $this->normalizeString(rtrim(substr($line, 97, 20)));
        $name = $note ?? $receiptId ?? '';
        $variableSymbol = $this->normalizeInt(substr($line, 61, 10));
        $constantSymbol = $this->normalizeInt(substr($line, 77, 4));
        $date = $this->parseFallbackDate(substr($line, 122, 6));

        return new Transaction(
            $keyGenerator->fromGpc($accountNumber, $date, $amount, $counterAccount, $name, $variableSymbol, $constantSymbol, $note),
            BankTransactionSource::GPC,
            $date,
            $amount,
            $counterAccount,
            $name,
            $variableSymbol,
            $constantSymbol,
            $note,
            $receiptId,
        );
    }

    private function applyFallbackAdditionalInformation(
        Transaction $transaction,
        string $line,
        string $accountNumber,
        BankTransactionKeyGenerator $keyGenerator,
    ): Transaction {
        $counterPartyName = $this->normalizeString(rtrim(substr($line, 35, 92)));

        if ($counterPartyName === null) {
            return $transaction;
        }

        return new Transaction(
            $keyGenerator->fromGpc(
                $accountNumber,
                $transaction->getDate(),
                $transaction->getAmount(),
                $transaction->getBankAccount(),
                $counterPartyName,
                $transaction->getVariableSymbol(),
                $transaction->getConstantSymbol(),
                $transaction->getNote(),
            ),
            $transaction->getSource(),
            $transaction->getDate(),
            $transaction->getAmount(),
            $transaction->getBankAccount(),
            $counterPartyName,
            $transaction->getVariableSymbol(),
            $transaction->getConstantSymbol(),
            $transaction->getNote(),
            $transaction->getSourceTransactionId(),
        );
    }

    private function resolveFallbackAmount(string $line, string $accountNumber): float
    {
        $amount = (float) ((int) ltrim(substr($line, 48, 12), '0')) / 100;
        $postingCode = (int) substr($line, 60, 1);
        $bankCode = $this->resolveBankCode($accountNumber);
        $postingCodeMap = $bankCode === '0300'
            ? [1 => 1, 2 => 2, 3 => 4, 4 => 5]
            : [1 => 1, 2 => 2, 4 => 4, 5 => 5];

        return match ($postingCodeMap[$postingCode] ?? null) {
            1 => -$amount,
            2 => $amount,
            4 => $amount,
            5 => -$amount,
            default => throw new RuntimeException('Nepodporovany GPC posting code: '.$postingCode),
        };
    }

    private function parseFallbackDate(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('dmyHis', $value.'120000');

        if ($date === false) {
            throw new RuntimeException('Nepodarilo se precist datum z GPC souboru: '.$value);
        }

        return $date;
    }

    private function normalizeCounterAccount(string $prefix, string $number, string $bankCode): ?string
    {
        $prefix = ltrim($prefix, '0');
        $number = ltrim($number, '0');
        $bankCode = trim($bankCode);

        if ($number === '' && $bankCode === '') {
            return null;
        }

        $prefixPart = $prefix === '' ? '' : $prefix.'-';
        $numberPart = $number === '' ? '0' : $number;

        return $prefixPart.$numberPart.($bankCode === '' ? '' : '/'.$bankCode);
    }

    private function normalizeStatementAccountNumber(string $rawAccountNumber): ?string
    {
        $rawAccountNumber = trim($rawAccountNumber);

        if ($rawAccountNumber === '' || trim($rawAccountNumber, '0') === '') {
            return null;
        }

        $prefix = ltrim(substr($rawAccountNumber, 0, 6), '0');
        $number = ltrim(substr($rawAccountNumber, 6, 10), '0');

        if ($number === '') {
            return null;
        }

        $prefixPart = $prefix === '' ? '' : $prefix.'-';

        return $prefixPart.$number;
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

        return isset($parts[1]) ? trim($parts[1]) : null;
    }

    private function decodeTextValue(string $value): string
    {
        if (preg_match('//u', $value) === 1) {
            return $value;
        }

        foreach (['Windows-1250', 'ISO-8859-2'] as $encoding) {
            $converted = iconv($encoding, 'UTF-8//IGNORE', $value);

            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }

        return $value;
    }
}
