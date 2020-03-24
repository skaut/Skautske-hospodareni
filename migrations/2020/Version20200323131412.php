<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200323131412 extends AbstractMigration
{
    private const RENAMED_TYPES = [
        'l' => 'airplane',
        'p' => 'on_foot',
        'mov' => 'motorcycle',
        'o' => 'train',
        'r' => 'express_train',
        'a' => 'bus',
        'auv' => 'car',
    ];

    public function getDescription() : string
    {
        return 'Map transport type as regular types instead of entities';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_command_types DROP FOREIGN KEY FK_DC7EBB9BF49490');
        $this->addSql('ALTER TABLE tc_commands ADD transport_types JSON NOT NULL COMMENT \'(DC2Type:transport_types)\'');
        $this->addSql('ALTER TABLE tc_travels CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');

        $this->addSql('ALTER TABLE tc_command_types CHANGE typeId typeId VARCHAR(255) NOT NULL');

        foreach (self::RENAMED_TYPES as $original => $new) {
            $this->addSql('UPDATE tc_command_types SET typeId = ? WHERE typeId = ?', [$new, $original]);
            $this->addSql('UPDATE tc_travels SET type = ? WHERE type = ?', [$new, $original]);
        }

        $this->addSql(<<<'SQL'
            UPDATE tc_commands c
                SET transport_types = (SELECT JSON_ARRAYAGG(typeId) FROM tc_command_types WHERE commandId = c.id)
        SQL);

        $this->addSql('DROP TABLE tc_command_types');
        $this->addSql('DROP TABLE tc_travelTypes');
    }

    public function down(Schema $schema) : void
    {
        // Since MySQL does not support unnest() function or any equivalent, this would be cumbersome to implement.
        // Backup was performed instead before deployment
    }
}
