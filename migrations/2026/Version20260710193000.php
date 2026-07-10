<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reply metadata to technical error reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report ADD reply_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reply_message LONGTEXT DEFAULT NULL, ADD reply_error LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP reply_sent_at, DROP reply_message, DROP reply_error');
    }
}
