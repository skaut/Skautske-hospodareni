<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store reporter email on technical error reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report ADD reporter_email VARCHAR(255) DEFAULT NULL AFTER reporter_display_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP reporter_email');
    }
}
