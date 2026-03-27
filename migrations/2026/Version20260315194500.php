<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function array_key_exists;
use function sprintf;

final class Version20260315194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'clean up legacy invoice VAT fields and align legacy transaction snapshot columns with long bank transaction keys';
    }

    public function up(Schema $schema): void
    {
        $this->modifyColumnIfExists('invoice', 'transactionId', 'VARCHAR(191) DEFAULT NULL');
        $this->modifyColumnIfExists('pa_payment', 'transactionId', 'VARCHAR(191) DEFAULT NULL');

        $this->dropColumnIfExists('invoice', 'supplier_vat_number');
        $this->dropColumnIfExists('invoice', 'supplier_vat_payer');
        $this->dropColumnIfExists('invoice_item', 'vat');
        $this->dropColumnIfExists('invoice_sequence', 'is_vat_payer');
        $this->dropColumnIfExists('invoice_sequence', 'vat_number');
        $this->dropColumnIfExists('invoice_unit_setting', 'vat_number');
        $this->dropColumnIfExists('invoice_unit_setting', 'vat_payer');
    }

    public function down(Schema $schema): void
    {
        $this->modifyColumnIfExists('invoice', 'transactionId', 'VARCHAR(64) DEFAULT NULL');
        $this->modifyColumnIfExists('pa_payment', 'transactionId', 'VARCHAR(64) DEFAULT NULL');

        $this->addColumnIfMissing('invoice', 'supplier_vat_number', 'VARCHAR(64) NOT NULL');
        $this->addColumnIfMissing('invoice', 'supplier_vat_payer', 'TINYINT(1) NOT NULL');
        $this->addColumnIfMissing('invoice_item', 'vat', 'NUMERIC(15, 2) DEFAULT NULL');
        $this->addColumnIfMissing('invoice_sequence', 'is_vat_payer', 'TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addColumnIfMissing('invoice_sequence', 'vat_number', 'VARCHAR(20) DEFAULT NULL');
        $this->addColumnIfMissing('invoice_unit_setting', 'vat_number', 'VARCHAR(64) DEFAULT NULL');
        $this->addColumnIfMissing('invoice_unit_setting', 'vat_payer', 'TINYINT(1) NOT NULL');
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (! $this->tableExists($table) || ! $this->columnExists($table, $column)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $column));
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (! $this->tableExists($table) || $this->columnExists($table, $column)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD %s %s', $table, $column, $definition));
    }

    private function modifyColumnIfExists(string $table, string $column, string $definition): void
    {
        if (! $this->tableExists($table) || ! $this->columnExists($table, $column)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s MODIFY %s %s', $table, $column, $definition));
    }

    private function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }

    private function columnExists(string $table, string $column): bool
    {
        return array_key_exists($column, $this->connection->createSchemaManager()->listTableColumns($table));
    }
}
