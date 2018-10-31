<?php declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181031175021 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql ("ALTER TABLE `ac_chitsCategory` ADD `virtual` int unsigned NOT NULL DEFAULT '0' AFTER `type`;");
        $this->addSql ("UPDATE `ac_chitsCategory` SET `virtual` = '1' WHERE `id` IN (7,9,13,14,15,16);");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql ("ALTER TABLE `ac_chitsCategory` DROP `virtual`;");
    }
}
