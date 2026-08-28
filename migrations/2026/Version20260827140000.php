<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let the administration override the lead strip under a page title';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page_help ADD lead_text VARCHAR(500) DEFAULT NULL AFTER page_key');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page_help DROP lead_text');
    }
}
