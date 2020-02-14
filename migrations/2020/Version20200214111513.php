<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200214111513 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remap enums as varchars';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_participants CHANGE isAccount isAccount VARCHAR(255) NOT NULL COMMENT '(DC2Type:string_enum)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_chitsCategory CHANGE type type VARCHAR(255) NOT NULL COMMENT '(DC2Type:string_enum)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_unit_budget_category CHANGE type type VARCHAR(255) NOT NULL COMMENT '(DC2Type:string_enum)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE log CHANGE type type VARCHAR(255) NOT NULL COMMENT '(DC2Type:string_enum)'
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE ac_participants CHANGE isAccount isAccount ENUM('N', 'Y') COLLATE utf8_czech_ci DEFAULT 'N' COMMENT 'placeno na účet?'");
        $this->addSql("ALTER TABLE ac_chitsCategory CHANGE type type ENUM('in', 'out')  COLLATE utf8_czech_ci NOT NULL DEFAULT 'out'");
        $this->addSql("ALTER TABLE ac_unit_budget_category CHANGE type type ENUM('in', 'out') COLLATE utf8_czech_ci NOT NULL DEFAULT 'out'");
        $this->addSql("ALTER TABLE log CHANGE type type ENUM('object', 'payment') CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL");
    }
}
