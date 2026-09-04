<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904105222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional YouTube video links to contextual page help';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page_help ADD youtube_title VARCHAR(255) DEFAULT NULL AFTER lead_text, ADD youtube_url VARCHAR(2048) DEFAULT NULL AFTER youtube_title');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page_help DROP youtube_url, DROP youtube_title');
    }
}
