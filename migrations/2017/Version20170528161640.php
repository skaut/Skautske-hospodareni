<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170528161640 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES (12, 'Neurčeno', 'np', 1, '101', '0')");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("DELETE FROM `ac_chitsCategory` WHERE (`id` = '12');");
    }
}
