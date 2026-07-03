<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

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
        $this->addSql(<<<'SQL'
INSERT INTO bank_transaction (
    bank_account_id,
    import_batch_id,
    source,
    transaction_key,
    source_transaction_id,
    date,
    amount,
    counter_account,
    counter_name,
    variable_symbol,
    constant_symbol,
    note,
    imported_at
)
SELECT
    g.bank_account_id,
    NULL,
    'fio',
    first_payment.transaction_key,
    first_payment.transaction_key,
    COALESCE(
        CONCAT(p.date, ' 00:00:00'),
        NULLIF(TRIM(CAST(p.closed_at AS CHAR)), ''),
        '2026-03-14 00:30:00'
    ),
    p.amount,
    NULLIF(TRIM(p.bank_account), ''),
    COALESCE(NULLIF(TRIM(p.transaction_payer), ''), ''),
    CAST(NULLIF(TRIM(p.variable_symbol), '') AS SIGNED),
    CAST(NULLIF(TRIM(CAST(p.constant_symbol AS CHAR)), '') AS SIGNED),
    NULLIF(TRIM(p.transaction_note), ''),
    COALESCE(
        NULLIF(TRIM(CAST(p.closed_at AS CHAR)), ''),
        CONCAT(p.date, ' 00:00:00'),
        '2026-03-14 00:30:00'
    )
FROM pa_payment p
INNER JOIN pa_group g ON g.id = p.group_id
INNER JOIN (
    SELECT TRIM(p2.transactionId) AS transaction_key, MIN(p2.id) AS payment_id
    FROM pa_payment p2
    INNER JOIN pa_group g2 ON g2.id = p2.group_id
    WHERE p2.transactionId IS NOT NULL
      AND TRIM(p2.transactionId) != ''
      AND g2.bank_account_id IS NOT NULL
    GROUP BY TRIM(p2.transactionId)
) first_payment ON first_payment.payment_id = p.id
LEFT JOIN bank_transaction existing_transaction ON existing_transaction.transaction_key = first_payment.transaction_key
WHERE existing_transaction.id IS NULL
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO bank_transaction_pairing (
    bank_transaction_id,
    payment_id,
    invoice_id,
    transaction_key,
    pairing_mode,
    paired_at,
    paired_by,
    cancelled_at,
    cancelled_by,
    cancellation_reason,
    historical_bank_account_id,
    historical_bank_account_name,
    historical_account_number,
    historical_bank_code
)
SELECT
    bt.id,
    p.id,
    NULL,
    TRIM(p.transactionId),
    'automatic',
    COALESCE(
        NULLIF(TRIM(CAST(p.closed_at AS CHAR)), ''),
        CONCAT(p.date, ' 00:00:00'),
        '2026-03-14 00:30:00'
    ),
    NULLIF(TRIM(p.closed_by_username), ''),
    NULL,
    NULL,
    NULL,
    g.bank_account_id,
    NULLIF(TRIM(ba.name), ''),
    CASE
        WHEN NULLIF(TRIM(ba.number_number), '') IS NULL THEN NULL
        WHEN NULLIF(TRIM(ba.number_prefix), '') IS NOT NULL THEN CONCAT(TRIM(ba.number_prefix), '-', TRIM(ba.number_number))
        ELSE TRIM(ba.number_number)
    END,
    NULLIF(TRIM(ba.number_bank_code), '')
FROM pa_payment p
INNER JOIN pa_group g ON g.id = p.group_id
LEFT JOIN pa_bank_account ba ON ba.id = g.bank_account_id
LEFT JOIN bank_transaction bt ON bt.transaction_key = TRIM(p.transactionId)
LEFT JOIN bank_transaction_pairing existing_pairing
    ON existing_pairing.payment_id = p.id
    AND existing_pairing.transaction_key = TRIM(p.transactionId)
WHERE p.transactionId IS NOT NULL
  AND TRIM(p.transactionId) != ''
  AND existing_pairing.id IS NULL
SQL);
    }

    private function migrateLegacyInvoicePairings(): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO bank_transaction (
    bank_account_id,
    import_batch_id,
    source,
    transaction_key,
    source_transaction_id,
    date,
    amount,
    counter_account,
    counter_name,
    variable_symbol,
    constant_symbol,
    note,
    imported_at
)
SELECT
    i.bank_account_id,
    NULL,
    'fio',
    first_invoice.transaction_key,
    first_invoice.transaction_key,
    COALESCE(
        CONCAT(i.date, ' 00:00:00'),
        NULLIF(TRIM(CAST(i.closed_at AS CHAR)), ''),
        '2026-03-14 00:30:00'
    ),
    COALESCE(totals.total_amount, 0.0),
    NULLIF(TRIM(i.bank_account), ''),
    COALESCE(NULLIF(TRIM(i.transaction_payer), ''), ''),
    CAST(NULLIF(TRIM(i.variable_symbol), '') AS SIGNED),
    NULL,
    NULLIF(TRIM(i.transaction_note), ''),
    COALESCE(
        NULLIF(TRIM(CAST(i.closed_at AS CHAR)), ''),
        CONCAT(i.date, ' 00:00:00'),
        '2026-03-14 00:30:00'
    )
FROM invoice i
INNER JOIN (
    SELECT TRIM(i2.transactionId) AS transaction_key, MIN(i2.id) AS invoice_id
    FROM invoice i2
    WHERE i2.transactionId IS NOT NULL
      AND TRIM(i2.transactionId) != ''
      AND i2.bank_account_id IS NOT NULL
    GROUP BY TRIM(i2.transactionId)
) first_invoice ON first_invoice.invoice_id = i.id
LEFT JOIN (
    SELECT invoice_id, SUM(CAST(price AS DECIMAL(15,2)) * quantity) AS total_amount
    FROM invoice_item
    GROUP BY invoice_id
) totals ON totals.invoice_id = i.id
LEFT JOIN bank_transaction existing_transaction ON existing_transaction.transaction_key = first_invoice.transaction_key
WHERE existing_transaction.id IS NULL
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO bank_transaction_pairing (
    bank_transaction_id,
    payment_id,
    invoice_id,
    transaction_key,
    pairing_mode,
    paired_at,
    paired_by,
    cancelled_at,
    cancelled_by,
    cancellation_reason,
    historical_bank_account_id,
    historical_bank_account_name,
    historical_account_number,
    historical_bank_code
)
SELECT
    bt.id,
    NULL,
    i.id,
    TRIM(i.transactionId),
    'automatic',
    COALESCE(
        NULLIF(TRIM(CAST(i.closed_at AS CHAR)), ''),
        CONCAT(i.date, ' 00:00:00'),
        '2026-03-14 00:30:00'
    ),
    NULLIF(TRIM(i.closed_by_username), ''),
    NULL,
    NULL,
    NULL,
    i.bank_account_id,
    COALESCE(NULLIF(TRIM(ba.name), ''), NULLIF(TRIM(i.bank_name), '')),
    CASE
        WHEN NULLIF(TRIM(COALESCE(ba.number_number, i.account_number_number)), '') IS NULL THEN NULL
        WHEN NULLIF(TRIM(COALESCE(ba.number_prefix, i.account_number_prefix)), '') IS NOT NULL THEN CONCAT(TRIM(COALESCE(ba.number_prefix, i.account_number_prefix)), '-', TRIM(COALESCE(ba.number_number, i.account_number_number)))
        ELSE TRIM(COALESCE(ba.number_number, i.account_number_number))
    END,
    COALESCE(NULLIF(TRIM(ba.number_bank_code), ''), NULLIF(TRIM(i.account_number_bank_code), ''))
FROM invoice i
LEFT JOIN pa_bank_account ba ON ba.id = i.bank_account_id
LEFT JOIN bank_transaction bt ON bt.transaction_key = TRIM(i.transactionId)
LEFT JOIN bank_transaction_pairing existing_pairing
    ON existing_pairing.invoice_id = i.id
    AND existing_pairing.transaction_key = TRIM(i.transactionId)
WHERE i.transactionId IS NOT NULL
  AND TRIM(i.transactionId) != ''
  AND existing_pairing.id IS NULL
SQL);
    }
}
