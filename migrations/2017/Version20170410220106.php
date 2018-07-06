<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170410220106 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE `pa_payment` SET `note` = '' WHERE `note` IS NULL");
        $this->addSql("ALTER TABLE `pa_payment` CHANGE `note` `note` VARCHAR(64) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema) : void
    {
    }
}
