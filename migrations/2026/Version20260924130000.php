<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove contextual help for the discontinued announcement detail page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM page_help WHERE page_key = ?', ['Announcements:detail']);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'INSERT IGNORE INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Announcements:detail', '[{"heading":"Platnost","text":"Tento detail je dostupný, dokud je oznámení zveřejněné a platné.","items":[]}]'],
        );
    }
}
