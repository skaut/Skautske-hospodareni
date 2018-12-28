<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181228133626 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_contracts DROP deleted;'); //uz se v ORM kodu nepouziva
        $this->addSql('ALTER TABLE tc_contracts CHANGE template template SMALLINT DEFAULT 2 NOT NULL;');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE `virtual` `virtual` TINYINT(1) DEFAULT 0 NOT NULL;');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE orderby orderby SMALLINT unsigned DEFAULT 100 NOT NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_contracts` ADD `deleted` tinyint(3) unsigned NOT NULL DEFAULT 0;');
        $this->addSql('ALTER TABLE tc_contracts CHANGE template template int(11) NOT NULL DEFAULT 2 COMMENT \'1-old, 2-podle NOZ\';');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE `virtual` `virtual` int(10) unsigned DEFAULT 0 NOT NULL;');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE orderby orderby  tinyint(3) unsigned DEFAULT 100 NOT NULL;');
    }
}
