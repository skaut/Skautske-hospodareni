<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190825050311 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP INDEX unit_id ON tc_contracts');
        $this->addSql(<<<SQL
            ALTER TABLE tc_contracts 
            CHANGE unit_id unit_id INT UNSIGNED NOT NULL, 
            CHANGE driver_birthday driver_birthday DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', 
            CHANGE start start DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', 
            CHANGE end end DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', 
            CHANGE template template SMALLINT NOT NULL COMMENT '1-old, 2-podle NOZ'
            SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            ALTER TABLE tc_contracts 
                CHANGE unit_id unit_id INT UNSIGNED NOT NULL COMMENT 'SkautIS ID jednotky', 
                CHANGE start start DATE DEFAULT NULL, 
                CHANGE end end DATE DEFAULT NULL, 
                CHANGE template template SMALLINT NOT NULL, 
                CHANGE driver_birthday driver_birthday DATE NOT NULL
        SQL);
        $this->addSql('CREATE INDEX unit_id ON tc_contracts (unit_id)');
    }
}
