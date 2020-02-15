<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200214130535 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Unify log table and tc_* tables mapping';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE file_path file_path VARCHAR(255) NOT NULL COMMENT \'(DC2Type:file_path)\'');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_contracts
                CHANGE driver_birthday driver_birthday DATE DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE start start DATE DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE end end DATE DEFAULT NULL COMMENT '(DC2Type:chronos_date)'
        SQL);
        $this->addSql('ALTER TABLE log CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE log CHANGE date date DATETIME NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_contracts
                CHANGE start start DATE DEFAULT NULL,
                CHANGE end end DATE DEFAULT NULL,
                CHANGE driver_birthday driver_birthday DATE DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE file_path file_path VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
