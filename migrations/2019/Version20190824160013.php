<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190824160013 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_vehicle 
                CHANGE unit_id unit_id INT UNSIGNED NOT NULL, 
                CHANGE type type VARCHAR(64) NOT NULL, 
                CHANGE registration registration VARCHAR(64) NOT NULL, 
                CHANGE consumption consumption DOUBLE PRECISION UNSIGNED NOT NULL, 
                CHANGE note note VARCHAR(64) NOT NULL, 
                CHANGE archived archived TINYINT(1) DEFAULT '0' NOT NULL
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_vehicle 
                CHANGE unit_id unit_id INT UNSIGNED NOT NULL COMMENT 'ID jednotky ze SkautISu ', 
                CHANGE type type VARCHAR(64) NOT NULL COLLATE utf8_czech_ci COMMENT 'značka auta ', 
                CHANGE registration registration VARCHAR(64) NOT NULL COLLATE utf8_czech_ci,
                CHANGE consumption consumption DOUBLE PRECISION UNSIGNED NOT NULL COMMENT 'spotřeba', 
                CHANGE note note VARCHAR(64) DEFAULT '' NOT NULL COLLATE utf8_czech_ci COMMENT 'volitelná poznámka ', 
                CHANGE archived archived TINYINT(1) DEFAULT '0' NOT NULL
        SQL);
    }
}
