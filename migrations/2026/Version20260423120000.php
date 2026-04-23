<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stamp image path to invoice unit settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice_unit_setting ADD stamp_image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice_unit_setting DROP stamp_image_path');
    }
}
