<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add resolution state to technical error reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report ADD resolution_state VARCHAR(20) DEFAULT NULL AFTER resolved_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP resolution_state');
    }
}
