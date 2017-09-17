<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


class Version20170917162823 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `ac_chitsCategory` (`label`, `short`, `type`, `orderby`, `deleted`) VALUES ('Převod do odd. pokladny', 'd', 2, '100', '0');");
        $this->addSql("INSERT INTO `ac_chitsCategory` (`label`, `short`, `type`, `orderby`, `deleted`) VALUES ('Převod do akce', 'a', 2, '100', '0');");
    }

    public function down(Schema $schema)
    {
    }
}
