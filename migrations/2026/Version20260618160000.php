<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add storage for technical error reports and diagnostic data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE technical_error_report (id INT UNSIGNED AUTO_INCREMENT NOT NULL, description LONGTEXT NOT NULL, reported_url VARCHAR(2048) DEFAULT NULL, reporter_user_id INT UNSIGNED NOT NULL, reporter_display_name VARCHAR(255) NOT NULL, role_id INT UNSIGNED DEFAULT NULL, role_name VARCHAR(255) DEFAULT NULL, unit_id INT UNSIGNED DEFAULT NULL, unit_name VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, app_release VARCHAR(255) NOT NULL, diagnostics JSON NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', notification_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', notification_error LONGTEXT DEFAULT NULL, INDEX technical_error_report_created_at_idx (created_at), INDEX technical_error_report_user_id_idx (reporter_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE technical_error_report');
    }
}
