<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align pa_payment.transactionId column length with Doctrine mapping (64 → 191)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_payment CHANGE transactionId transactionId VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_payment CHANGE transactionId transactionId VARCHAR(64) DEFAULT NULL');
    }
}
