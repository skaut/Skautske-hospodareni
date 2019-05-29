<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190529204201 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_camp_participants
                CHANGE actionId event_id INT UNSIGNED,
                ADD event_type VARCHAR(7) COLLATE utf8_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)'
        SQL);
        $this->addSql('UPDATE ac_camp_participants SET event_type = \'camp\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(
            'ALTER TABLE ac_camp_participants CHANGE event_id actionId INT UNSIGNED NOT NULL, DROP event_type'
        );
    }
}
