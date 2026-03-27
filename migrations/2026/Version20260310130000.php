<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create final invoice and bank transaction schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_bank_account ADD transaction_source VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_bank_account CHANGE number_number number_number VARCHAR(10) DEFAULT NULL, CHANGE number_bank_code number_bank_code VARCHAR(4) DEFAULT NULL');

        $this->addSql("CREATE TABLE invoice_sequence (id INT UNSIGNED AUTO_INCREMENT NOT NULL, bank_account_id INT DEFAULT NULL, unit INT NOT NULL, sequence_id INT DEFAULT 1 NOT NULL, sequence VARCHAR(20) NOT NULL, first_number VARCHAR(10) DEFAULT '00001' NOT NULL, year INT DEFAULT NULL, description VARCHAR(255) NOT NULL, oauth_id CHAR(36) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:oauth_id)', default_due_date INT DEFAULT NULL, automatic_pairing_enabled TINYINT(1) DEFAULT 0 NOT NULL, pairing_days_back INT DEFAULT NULL, last_pairing DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', state VARCHAR(20) NOT NULL COMMENT '(DC2Type:string_enum)', phone VARCHAR(255) DEFAULT NULL, INDEX IDX_4DAE8D7312CB990C (bank_account_id), UNIQUE INDEX invoice_sequence_id_unit_sequence_year_unique (unit, sequence_id, year), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql('CREATE TABLE invoice_unit_setting (id INT UNSIGNED AUTO_INCREMENT NOT NULL, unit INT NOT NULL, year INT NOT NULL, name VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, city VARCHAR(64) NOT NULL, zipcode VARCHAR(10) NOT NULL, company_number VARCHAR(64) NOT NULL, phone VARCHAR(64) DEFAULT NULL, UNIQUE INDEX invoice_unit_setting_unit_year_unique (unit, year), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        $this->addSql("CREATE TABLE invoice (id INT UNSIGNED AUTO_INCREMENT NOT NULL, sequence_id INT UNSIGNED NOT NULL, bank_account_id INT DEFAULT NULL, invoice_id INT DEFAULT NULL, invoice_number VARCHAR(255) DEFAULT NULL, issued_by VARCHAR(255) NOT NULL, due_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', date_of_issue DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', date_of_tax_payment DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', state VARCHAR(20) NOT NULL COMMENT '(DC2Type:string_enum)', variable_symbol VARCHAR(10) NOT NULL COMMENT '(DC2Type:variable_symbol)', closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', closed_by_username VARCHAR(64) DEFAULT NULL, payment_type VARCHAR(20) NOT NULL COMMENT '(DC2Type:string_enum)', bank_name VARCHAR(255) DEFAULT NULL, account_number_prefix VARCHAR(6) DEFAULT NULL, account_number_number VARCHAR(10) DEFAULT NULL, account_number_bank_code VARCHAR(4) DEFAULT NULL, account_number_bank_name VARCHAR(255) DEFAULT NULL, account_number_iban VARCHAR(255) DEFAULT NULL, account_number_bic VARCHAR(255) DEFAULT NULL, iban VARCHAR(255) DEFAULT NULL, bic VARCHAR(255) DEFAULT NULL, sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', sent_by VARCHAR(255) DEFAULT NULL, cash_receipt_number VARCHAR(64) DEFAULT NULL, supplier_unit_id INT NOT NULL, supplier_name VARCHAR(255) NOT NULL, supplier_company_number VARCHAR(64) NOT NULL, supplier_phone VARCHAR(64) DEFAULT NULL, supplier_address_street VARCHAR(255) NOT NULL, supplier_address_city VARCHAR(64) NOT NULL, supplier_address_zip_code VARCHAR(10) NOT NULL, supplier_address_street_number VARCHAR(10) DEFAULT NULL, supplier_address_street_number_suffix VARCHAR(10) DEFAULT NULL, supplier_address_country_name VARCHAR(64) DEFAULT NULL, supplier_address_country_code VARCHAR(64) DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, customer_company_number VARCHAR(64) NOT NULL, customer_vat_number VARCHAR(64) NOT NULL, customer_vat_payer TINYINT(1) NOT NULL, customer_address_street VARCHAR(255) NOT NULL, customer_address_city VARCHAR(64) NOT NULL, customer_address_zip_code VARCHAR(10) NOT NULL, customer_address_street_number VARCHAR(10) DEFAULT NULL, customer_address_street_number_suffix VARCHAR(10) DEFAULT NULL, customer_address_country_name VARCHAR(64) DEFAULT NULL, customer_address_country_code VARCHAR(64) DEFAULT NULL, transactionId VARCHAR(191) DEFAULT NULL, bank_account VARCHAR(64) DEFAULT NULL, transaction_payer VARCHAR(255) DEFAULT NULL, transaction_note VARCHAR(255) DEFAULT NULL, date DATE DEFAULT NULL COMMENT '(DC2Type:chronos_date)', INDEX IDX_9065174498FB19AE (sequence_id), INDEX IDX_9065174412CB990C (bank_account_id), UNIQUE INDEX invoice_sequence_number_unique (sequence_id, invoice_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE invoice_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, invoice_id INT UNSIGNED NOT NULL, quantity INT NOT NULL, unit VARCHAR(10) NOT NULL, purpose VARCHAR(255) NOT NULL, price NUMERIC(15, 2) NOT NULL COMMENT '(DC2Type:big_decimal)', INDEX IDX_1DDE477B2989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE invoice_email_recipient (id INT UNSIGNED AUTO_INCREMENT NOT NULL, invoice_id INT UNSIGNED NOT NULL, email_address VARCHAR(255) NOT NULL COMMENT '(DC2Type:email_address)', INDEX IDX_23EBCB2F2989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql('CREATE TABLE invoice_sequence_email (id INT UNSIGNED AUTO_INCREMENT NOT NULL, sequence_id INT UNSIGNED NOT NULL, type VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, INDEX IDX_7C9CEB0198FB19AE (sequence_id), UNIQUE INDEX invoice_sequence_email_type_unique (sequence_id, type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        $this->addSql("CREATE TABLE invoice_sent_email (id INT UNSIGNED AUTO_INCREMENT NOT NULL, invoice_id INT UNSIGNED NOT NULL, type VARCHAR(50) NOT NULL, time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', sender_name VARCHAR(255) NOT NULL, successful TINYINT(1) DEFAULT 1 NOT NULL, error_message LONGTEXT DEFAULT NULL, INDEX IDX_10F295622989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE bank_transaction_import_batch (id INT UNSIGNED AUTO_INCREMENT NOT NULL, bank_account_id INT NOT NULL, source VARCHAR(20) NOT NULL, file_name VARCHAR(255) NOT NULL, file_hash VARCHAR(64) NOT NULL, imported_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', imported_by VARCHAR(255) NOT NULL, transaction_count INT NOT NULL, new_transaction_count INT NOT NULL, INDEX IDX_49021CF912CB990C (bank_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE bank_transaction (id INT UNSIGNED AUTO_INCREMENT NOT NULL, bank_account_id INT NOT NULL, import_batch_id INT UNSIGNED DEFAULT NULL, source VARCHAR(20) NOT NULL, transaction_key VARCHAR(191) NOT NULL, source_transaction_id VARCHAR(191) DEFAULT NULL, date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', amount DOUBLE PRECISION NOT NULL, counter_account VARCHAR(64) DEFAULT NULL, counter_name VARCHAR(255) NOT NULL, variable_symbol INT DEFAULT NULL, constant_symbol INT DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, imported_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX bank_transaction_key_unique (transaction_key), INDEX IDX_50BCB3AE12CB990C (bank_account_id), INDEX IDX_50BCB3AE5A310080 (import_batch_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE bank_transaction_pairing (id INT UNSIGNED AUTO_INCREMENT NOT NULL, bank_transaction_id INT UNSIGNED DEFAULT NULL, payment_id INT DEFAULT NULL, invoice_id INT UNSIGNED DEFAULT NULL, transaction_key VARCHAR(191) NOT NULL, pairing_mode VARCHAR(20) NOT NULL, paired_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', paired_by VARCHAR(255) DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', cancelled_by VARCHAR(255) DEFAULT NULL, cancellation_reason VARCHAR(255) DEFAULT NULL, historical_bank_account_id INT DEFAULT NULL, historical_bank_account_name VARCHAR(255) DEFAULT NULL, historical_account_number VARCHAR(64) DEFAULT NULL, historical_bank_code VARCHAR(16) DEFAULT NULL, INDEX IDX_71FB1C7B898B7D6 (bank_transaction_id), INDEX IDX_71FB1C74C3A3BB (payment_id), INDEX IDX_71FB1C72989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE invoice_sequence ADD CONSTRAINT FK_4DAE8D7312CB990C FOREIGN KEY (bank_account_id) REFERENCES pa_bank_account (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174498FB19AE FOREIGN KEY (sequence_id) REFERENCES invoice_sequence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174412CB990C FOREIGN KEY (bank_account_id) REFERENCES pa_bank_account (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice_email_recipient ADD CONSTRAINT FK_23EBCB2F2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice_sequence_email ADD CONSTRAINT FK_7C9CEB0198FB19AE FOREIGN KEY (sequence_id) REFERENCES invoice_sequence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice_sent_email ADD CONSTRAINT FK_10F295622989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bank_transaction_import_batch ADD CONSTRAINT FK_49021CF912CB990C FOREIGN KEY (bank_account_id) REFERENCES pa_bank_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bank_transaction ADD CONSTRAINT FK_50BCB3AE12CB990C FOREIGN KEY (bank_account_id) REFERENCES pa_bank_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bank_transaction ADD CONSTRAINT FK_50BCB3AE5A310080 FOREIGN KEY (import_batch_id) REFERENCES bank_transaction_import_batch (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE bank_transaction_pairing ADD CONSTRAINT FK_71FB1C7B898B7D6 FOREIGN KEY (bank_transaction_id) REFERENCES bank_transaction (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE bank_transaction_pairing ADD CONSTRAINT FK_71FB1C74C3A3BB FOREIGN KEY (payment_id) REFERENCES pa_payment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bank_transaction_pairing ADD CONSTRAINT FK_71FB1C72989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_transaction_pairing DROP FOREIGN KEY FK_71FB1C7B898B7D6');
        $this->addSql('ALTER TABLE bank_transaction_pairing DROP FOREIGN KEY FK_71FB1C74C3A3BB');
        $this->addSql('ALTER TABLE bank_transaction_pairing DROP FOREIGN KEY FK_71FB1C72989F1FD');
        $this->addSql('ALTER TABLE bank_transaction DROP FOREIGN KEY FK_50BCB3AE12CB990C');
        $this->addSql('ALTER TABLE bank_transaction DROP FOREIGN KEY FK_50BCB3AE5A310080');
        $this->addSql('ALTER TABLE bank_transaction_import_batch DROP FOREIGN KEY FK_49021CF912CB990C');
        $this->addSql('ALTER TABLE invoice_sent_email DROP FOREIGN KEY FK_10F295622989F1FD');
        $this->addSql('ALTER TABLE invoice_sequence_email DROP FOREIGN KEY FK_7C9CEB0198FB19AE');
        $this->addSql('ALTER TABLE invoice_email_recipient DROP FOREIGN KEY FK_23EBCB2F2989F1FD');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B2989F1FD');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_9065174498FB19AE');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_9065174412CB990C');
        $this->addSql('ALTER TABLE invoice_sequence DROP FOREIGN KEY FK_4DAE8D7312CB990C');

        $this->addSql('DROP TABLE bank_transaction_pairing');
        $this->addSql('DROP TABLE bank_transaction');
        $this->addSql('DROP TABLE bank_transaction_import_batch');
        $this->addSql('DROP TABLE invoice_sent_email');
        $this->addSql('DROP TABLE invoice_sequence_email');
        $this->addSql('DROP TABLE invoice_email_recipient');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_unit_setting');
        $this->addSql('DROP TABLE invoice_sequence');

        $this->addSql('ALTER TABLE pa_bank_account DROP transaction_source');
        $this->addSql('ALTER TABLE pa_bank_account CHANGE number_number number_number VARCHAR(10) NOT NULL, CHANGE number_bank_code number_bank_code VARCHAR(4) NOT NULL');
    }
}
