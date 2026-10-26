<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026220000 extends AbstractMigration
{
    private const EQUIPMENT_CATEGORY_ID = 19;

    public function getDescription(): string
    {
        return 'Make the equipment expense category available to unit cashbooks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT IGNORE INTO ac_chitsCategory_object (category_id, type) VALUES (?, ?)',
            [self::EQUIPMENT_CATEGORY_ID, 'unit'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM ac_chitsCategory_object WHERE category_id = ? AND type = ?',
            [self::EQUIPMENT_CATEGORY_ID, 'unit'],
        );
    }
}
