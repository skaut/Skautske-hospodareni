<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\QueryHandlers;

use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\DTO\Stat\Counter;
use App\Model\Invoice\Enum\InvoiceState;
use App\Model\Payment\Group;
use App\Model\Payment\Payment\State;
use App\Model\Stat\ReadModel\Queries\LocalUnitStatisticsQuery;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class LocalUnitStatisticsQueryHandler
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return array<int, Counter> */
    public function __invoke(LocalUnitStatisticsQuery $query): array
    {
        $statistics = [];
        $this->addPaymentStatistics($statistics, $query);
        $this->addInvoiceStatistics($statistics, $query);
        $this->addBankStatistics($statistics, $query);
        $this->addBugReportStatistics($statistics, $query);

        return $statistics;
    }

    /** @param array<int, Counter> $statistics */
    private function addPaymentStatistics(array &$statistics, LocalUnitStatisticsQuery $query): void
    {
        $sql = <<<'SQL'
            SELECT
                gu.unit_id,
                COUNT(DISTINCT CASE WHEN g.state = :groupOpen THEN g.id END) AS groups_open,
                COUNT(DISTINCT CASE WHEN g.state = :groupClosed THEN g.id END) AS groups_closed,
                COUNT(DISTINCT p.id) AS payments_total,
                COUNT(DISTINCT CASE WHEN p.state = :paymentPreparing THEN p.id END) AS payments_preparing,
                COUNT(DISTINCT CASE WHEN p.state = :paymentCompleted THEN p.id END) AS payments_completed,
                COUNT(DISTINCT CASE WHEN p.state = :paymentCanceled THEN p.id END) AS payments_canceled,
                COALESCE(SUM(p.amount), 0) AS amount_total,
                COALESCE(SUM(CASE WHEN p.state = :paymentCompleted THEN p.amount ELSE 0 END), 0) AS amount_completed,
                COALESCE(SUM(btp.automatic_pairing_count), 0) AS automatic_pairings
            FROM pa_group_unit gu
            INNER JOIN pa_group g ON g.id = gu.group_id
            LEFT JOIN pa_payment p ON p.group_id = g.id
            LEFT JOIN (
                SELECT payment_id, COUNT(id) AS automatic_pairing_count
                FROM bank_transaction_pairing
                WHERE cancelled_at IS NULL
                  AND pairing_mode = :automaticPairing
                GROUP BY payment_id
            ) btp ON btp.payment_id = p.id
            WHERE gu.unit_id IN (:unitIds)
              AND YEAR(COALESCE(g.created_at, p.due_date)) = :year
            GROUP BY gu.unit_id
SQL;

        foreach ($this->connection->executeQuery($sql, [
            'unitIds' => $query->getUnitIds(),
            'year' => $query->getYear(),
            'groupOpen' => Group::STATE_OPEN,
            'groupClosed' => Group::STATE_CLOSED,
            'paymentPreparing' => State::PREPARING,
            'paymentCompleted' => State::COMPLETED,
            'paymentCanceled' => State::CANCELED,
            'automaticPairing' => BankTransactionPairingMode::AUTOMATIC->value,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllAssociative() as $row) {
            $this->counter($statistics, (int) $row['unit_id'])->addPaymentStats(
                (int) $row['groups_open'],
                (int) $row['groups_closed'],
                (int) $row['payments_total'],
                (int) $row['payments_preparing'],
                (int) $row['payments_completed'],
                (int) $row['payments_canceled'],
                (float) $row['amount_total'],
                (float) $row['amount_completed'],
                (int) $row['automatic_pairings'],
            );
        }
    }

    /** @param array<int, Counter> $statistics */
    private function addInvoiceStatistics(array &$statistics, LocalUnitStatisticsQuery $query): void
    {
        $sql = <<<'SQL'
            SELECT
                s.unit AS unit_id,
                COUNT(DISTINCT i.id) AS invoices_total,
                COUNT(DISTINCT CASE WHEN i.state = :issued THEN i.id END) AS invoices_issued,
                COUNT(DISTINCT CASE WHEN i.state = :delivered THEN i.id END) AS invoices_delivered,
                COUNT(DISTINCT CASE WHEN i.state = :paid THEN i.id END) AS invoices_paid,
                COUNT(DISTINCT CASE WHEN i.state = :cancelled THEN i.id END) AS invoices_cancelled,
                COUNT(DISTINCT CASE WHEN i.sent_at IS NOT NULL THEN i.id END) AS invoices_sent,
                COALESCE(SUM(CASE WHEN i.state != :cancelled THEN totals.total_amount ELSE 0 END), 0) AS amount_total,
                COALESCE(SUM(CASE WHEN i.state = :paid THEN totals.total_amount ELSE 0 END), 0) AS amount_paid
            FROM invoice_sequence s
            INNER JOIN invoice i ON i.sequence_id = s.id
            LEFT JOIN (
                SELECT invoice_id, SUM(CAST(price AS DECIMAL(15,2)) * quantity) AS total_amount
                FROM invoice_item
                GROUP BY invoice_id
            ) totals ON totals.invoice_id = i.id
            WHERE s.unit IN (:unitIds)
              AND COALESCE(s.year, YEAR(i.date_of_issue)) = :year
            GROUP BY s.unit
SQL;

        foreach ($this->connection->executeQuery($sql, [
            'unitIds' => $query->getUnitIds(),
            'year' => $query->getYear(),
            'issued' => InvoiceState::ISSUED,
            'delivered' => InvoiceState::DELIVERED,
            'paid' => InvoiceState::PAID,
            'cancelled' => InvoiceState::CANCELLED,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllAssociative() as $row) {
            $this->counter($statistics, (int) $row['unit_id'])->addInvoiceStats(
                (int) $row['invoices_total'],
                (int) $row['invoices_issued'],
                (int) $row['invoices_delivered'],
                (int) $row['invoices_paid'],
                (int) $row['invoices_cancelled'],
                (int) $row['invoices_sent'],
                (float) $row['amount_total'],
                (float) $row['amount_paid'],
            );
        }
    }

    /** @param array<int, Counter> $statistics */
    private function addBankStatistics(array &$statistics, LocalUnitStatisticsQuery $query): void
    {
        $sql = <<<'SQL'
            SELECT
                ba.unit_id,
                COUNT(DISTINCT ba.id) AS accounts_total,
                COUNT(DISTINCT CASE WHEN ba.transaction_source = 'fio' THEN ba.id END) AS accounts_fio,
                COUNT(DISTINCT CASE WHEN ba.transaction_source = 'gpc' THEN ba.id END) AS accounts_gpc,
                COUNT(DISTINCT bt.id) AS transactions_total,
                COUNT(DISTINCT CASE WHEN bt.id IS NOT NULL AND btp.id IS NULL THEN bt.id END) AS transactions_unpaired,
                MAX(bt.imported_at) AS last_import_at
            FROM pa_bank_account ba
            LEFT JOIN bank_transaction bt ON bt.bank_account_id = ba.id AND YEAR(bt.date) = :year
            LEFT JOIN bank_transaction_pairing btp ON btp.bank_transaction_id = bt.id AND btp.cancelled_at IS NULL
            WHERE ba.unit_id IN (:unitIds)
            GROUP BY ba.unit_id
SQL;

        foreach ($this->connection->executeQuery($sql, [
            'unitIds' => $query->getUnitIds(),
            'year' => $query->getYear(),
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllAssociative() as $row) {
            $this->counter($statistics, (int) $row['unit_id'])->addBankStats(
                (int) $row['accounts_total'],
                (int) $row['accounts_fio'],
                (int) $row['accounts_gpc'],
                (int) $row['transactions_total'],
                (int) $row['transactions_unpaired'],
                $row['last_import_at'] !== null ? new DateTimeImmutable((string) $row['last_import_at']) : null,
            );
        }
    }

    /** @param array<int, Counter> $statistics */
    private function addBugReportStatistics(array &$statistics, LocalUnitStatisticsQuery $query): void
    {
        $sql = <<<'SQL'
            SELECT
                unit_id,
                COUNT(id) AS reports_total,
                COUNT(CASE WHEN resolved_at IS NULL THEN 1 END) AS reports_open,
                COUNT(CASE WHEN resolution_state = 'fixed' THEN 1 END) AS reports_fixed,
                COUNT(CASE WHEN resolution_state = 'rejected' THEN 1 END) AS reports_rejected,
                COUNT(CASE WHEN resolved_at IS NOT NULL THEN 1 END) AS reports_resolved,
                COALESCE(SUM(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, resolved_at) ELSE 0 END), 0) AS resolution_seconds
            FROM technical_error_report
            WHERE unit_id IN (:unitIds)
              AND YEAR(created_at) = :year
            GROUP BY unit_id
SQL;

        foreach ($this->connection->executeQuery($sql, [
            'unitIds' => $query->getUnitIds(),
            'year' => $query->getYear(),
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllAssociative() as $row) {
            $this->counter($statistics, (int) $row['unit_id'])->addBugReportStats(
                (int) $row['reports_total'],
                (int) $row['reports_open'],
                (int) $row['reports_fixed'],
                (int) $row['reports_rejected'],
                (int) $row['reports_resolved'],
                (int) $row['resolution_seconds'],
            );
        }
    }

    /** @param array<int, Counter> $statistics */
    private function counter(array &$statistics, int $unitId): Counter
    {
        return $statistics[$unitId] ??= new Counter();
    }
}
