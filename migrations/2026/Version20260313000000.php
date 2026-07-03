<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'extend bank transaction variable symbol range before legacy pairing backfill';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_transaction MODIFY variable_symbol BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_transaction MODIFY variable_symbol INT DEFAULT NULL');
    }
}
