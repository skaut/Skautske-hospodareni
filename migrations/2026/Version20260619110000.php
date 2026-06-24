<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow technical error reports to be marked as resolved';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE technical_error_report ADD resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD INDEX technical_error_report_resolved_at_idx (resolved_at)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP INDEX technical_error_report_resolved_at_idx, DROP resolved_at');
    }
}
