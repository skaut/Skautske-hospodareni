<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add screenshot attachment metadata to technical error reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report ADD screenshot_path VARCHAR(255) DEFAULT NULL, ADD screenshot_original_name VARCHAR(255) DEFAULT NULL, ADD screenshot_content_type VARCHAR(100) DEFAULT NULL, ADD screenshot_size INT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report DROP screenshot_path, DROP screenshot_original_name, DROP screenshot_content_type, DROP screenshot_size');
    }
}
