<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130211802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add bank account detail';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pa_bank_account ADD number_bank_name VARCHAR(255) DEFAULT NULL, ADD number_iban VARCHAR(255) DEFAULT NULL, ADD number_bic VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pa_bank_account DROP number_bank_name, DROP number_iban, DROP number_bic');
    }
}
