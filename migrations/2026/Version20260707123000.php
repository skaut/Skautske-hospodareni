<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store technical error report resolution messages and notification state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE technical_error_report ADD resolution_message LONGTEXT DEFAULT NULL, ADD resolution_notification_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD resolution_notification_error LONGTEXT DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP resolution_message, DROP resolution_notification_sent_at, DROP resolution_notification_error');
    }
}
