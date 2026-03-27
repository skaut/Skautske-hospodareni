<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

use function trim;

final class Version20260314003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'backfill bank transaction source, invoice bank account relation and legacy payment and invoice pairings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE pa_bank_account SET transaction_source = CASE WHEN number_bank_code = '2010' THEN 'fio' ELSE 'gpc' END WHERE transaction_source IS NULL");
        $this->addSql('UPDATE invoice i INNER JOIN invoice_sequence s ON i.sequence_id = s.id SET i.bank_account_id = s.bank_account_id WHERE i.bank_account_id IS NULL');

        $this->migrateLegacyPaymentPairings();
        $this->migrateLegacyInvoicePairings();
    }

    public function down(Schema $schema): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }

    private function migrateLegacyPaymentPairings(): void
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT
    p.id AS payment_id,
    p.transactionId AS transaction_id,
    p.bank_account AS counter_account,
    p.transaction_payer AS transaction_payer,
    p.transaction_note AS transaction_note,
    p.date AS transaction_date,
    p.closed_at,
    p.closed_by_username,
    p.amount,
    p.variable_symbol,
    p.constant_symbol,
    g.bank_account_id AS source_bank_account_id,
    ba.name AS source_bank_account_name,
    ba.number_prefix AS source_account_prefix,
    ba.number_number AS source_account_number,
    ba.number_bank_code AS source_bank_code
FROM pa_payment p
INNER JOIN pa_group g ON g.id = p.group_id
LEFT JOIN pa_bank_account ba ON ba.id = g.bank_account_id
WHERE p.transactionId IS NOT NULL AND p.transactionId != ''
SQL);

        foreach ($rows as $row) {
            $transactionKey = trim((string) $row['transaction_id']);
            $pairedAt = $this->resolveDateTime($row['closed_at'], $row['transaction_date']);
            $historicalAccountNumber = $this->buildAccountNumber($row['source_account_prefix'], $row['source_account_number']);
            $bankTransactionId = $this->findOrCreateBankTransaction(
                $row['source_bank_account_id'] !== null ? (int) $row['source_bank_account_id'] : null,
                $transactionKey,
                $transactionKey,
                $row['transaction_date'],
                (float) $row['amount'],
                $this->normalizeString($row['counter_account']),
                $this->normalizeString($row['transaction_payer']) ?? '',
                $this->normalizeInt($row['variable_symbol']),
                $this->normalizeInt($row['constant_symbol']),
                $this->normalizeString($row['transaction_note']),
                $pairedAt,
            );

            $this->connection->insert('bank_transaction_pairing', [
                'bank_transaction_id' => $bankTransactionId,
                'payment_id' => (int) $row['payment_id'],
                'invoice_id' => null,
                'transaction_key' => $transactionKey,
                'pairing_mode' => 'automatic',
                'paired_at' => $pairedAt,
                'paired_by' => $this->normalizeString($row['closed_by_username']),
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
                'historical_bank_account_id' => $row['source_bank_account_id'] !== null ? (int) $row['source_bank_account_id'] : null,
                'historical_bank_account_name' => $this->normalizeString($row['source_bank_account_name']),
                'historical_account_number' => $historicalAccountNumber,
                'historical_bank_code' => $this->normalizeString($row['source_bank_code']),
            ]);
        }
    }

    private function migrateLegacyInvoicePairings(): void
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT
    i.id AS invoice_id,
    i.transactionId AS transaction_id,
    i.bank_account AS counter_account,
    i.transaction_payer AS transaction_payer,
    i.transaction_note AS transaction_note,
    i.date AS transaction_date,
    i.closed_at,
    i.closed_by_username,
    i.variable_symbol,
    i.bank_account_id AS source_bank_account_id,
    ba.name AS source_bank_account_name,
    ba.number_prefix AS source_account_prefix,
    ba.number_number AS source_account_number,
    ba.number_bank_code AS source_bank_code,
    i.bank_name AS snapshot_bank_name,
    i.account_number_prefix AS snapshot_account_prefix,
    i.account_number_number AS snapshot_account_number,
    i.account_number_bank_code AS snapshot_bank_code,
    totals.total_amount
FROM invoice i
LEFT JOIN pa_bank_account ba ON ba.id = i.bank_account_id
LEFT JOIN (
    SELECT invoice_id, SUM(CAST(price AS DECIMAL(15,2)) * quantity) AS total_amount
    FROM invoice_item
    GROUP BY invoice_id
) totals ON totals.invoice_id = i.id
WHERE i.transactionId IS NOT NULL AND i.transactionId != ''
SQL);

        foreach ($rows as $row) {
            $transactionKey = trim((string) $row['transaction_id']);
            $pairedAt = $this->resolveDateTime($row['closed_at'], $row['transaction_date']);
            $historicalBankAccountId = $row['source_bank_account_id'] !== null ? (int) $row['source_bank_account_id'] : null;
            $historicalBankAccountName = $this->normalizeString($row['source_bank_account_name'])
                ?? $this->normalizeString($row['snapshot_bank_name']);
            $historicalAccountNumber = $this->buildAccountNumber(
                $row['source_account_prefix'] ?? $row['snapshot_account_prefix'],
                $row['source_account_number'] ?? $row['snapshot_account_number'],
            );
            $historicalBankCode = $this->normalizeString($row['source_bank_code'])
                ?? $this->normalizeString($row['snapshot_bank_code']);

            $bankTransactionId = $this->findOrCreateBankTransaction(
                $historicalBankAccountId,
                $transactionKey,
                $transactionKey,
                $row['transaction_date'],
                (float) ($row['total_amount'] ?? 0.0),
                $this->normalizeString($row['counter_account']),
                $this->normalizeString($row['transaction_payer']) ?? '',
                $this->normalizeInt($row['variable_symbol']),
                null,
                $this->normalizeString($row['transaction_note']),
                $pairedAt,
            );

            $this->connection->insert('bank_transaction_pairing', [
                'bank_transaction_id' => $bankTransactionId,
                'payment_id' => null,
                'invoice_id' => (int) $row['invoice_id'],
                'transaction_key' => $transactionKey,
                'pairing_mode' => 'automatic',
                'paired_at' => $pairedAt,
                'paired_by' => $this->normalizeString($row['closed_by_username']),
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
                'historical_bank_account_id' => $historicalBankAccountId,
                'historical_bank_account_name' => $historicalBankAccountName,
                'historical_account_number' => $historicalAccountNumber,
                'historical_bank_code' => $historicalBankCode,
            ]);
        }
    }

    private function findOrCreateBankTransaction(
        ?int $bankAccountId,
        string $transactionKey,
        string $sourceTransactionId,
        ?string $transactionDate,
        float $amount,
        ?string $counterAccount,
        string $counterName,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?string $note,
        string $importedAt,
    ): ?int {
        $existingId = $this->connection->fetchOne(
            'SELECT id FROM bank_transaction WHERE transaction_key = ?',
            [$transactionKey],
        );

        if ($existingId !== false) {
            return (int) $existingId;
        }

        if ($bankAccountId === null) {
            return null;
        }

        $date = $this->resolveDateTime(null, $transactionDate, $importedAt);

        $this->connection->insert('bank_transaction', [
            'bank_account_id' => $bankAccountId,
            'import_batch_id' => null,
            'source' => 'fio',
            'transaction_key' => $transactionKey,
            'source_transaction_id' => $sourceTransactionId,
            'date' => $date,
            'amount' => $amount,
            'counter_account' => $counterAccount,
            'counter_name' => $counterName,
            'variable_symbol' => $variableSymbol,
            'constant_symbol' => $constantSymbol,
            'note' => $note,
            'imported_at' => $importedAt,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function resolveDateTime(?string $closedAt, ?string $transactionDate, ?string $fallback = null): string
    {
        $closedAt = $this->normalizeString($closedAt);

        if ($closedAt !== null) {
            return $closedAt;
        }

        $transactionDate = $this->normalizeString($transactionDate);

        if ($transactionDate !== null) {
            return $transactionDate . ' 00:00:00';
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return '2026-03-14 00:30:00';
    }

    private function buildAccountNumber(?string $prefix, ?string $number): ?string
    {
        $number = $this->normalizeString($number);

        if ($number === null) {
            return null;
        }

        $prefix = $this->normalizeString($prefix);

        return $prefix !== null
            ? $prefix . '-' . $number
            : $number;
    }

    private function normalizeString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeInt(int|string|null $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
