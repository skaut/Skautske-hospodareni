<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store GitHub issue links for technical error reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report ADD github_issue_number INT UNSIGNED DEFAULT NULL, ADD github_issue_url VARCHAR(2048) DEFAULT NULL, ADD github_issue_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD github_sync_error LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE technical_error_report_reply ADD github_comment_url VARCHAR(2048) DEFAULT NULL, ADD github_comment_error LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP github_issue_number, DROP github_issue_url, DROP github_issue_created_at, DROP github_sync_error');
        $this->addSql('ALTER TABLE technical_error_report_reply DROP github_comment_url, DROP github_comment_error');
    }
}
