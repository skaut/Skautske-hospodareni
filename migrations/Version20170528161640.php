<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170528161640 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES (12, 'Neurčeno', 'np', 1, '101', '0')");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `ac_chitsCategory` WHERE (`id` = '12');");
    }
}
