<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170531125856 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_command_types` ADD UNIQUE `unique_relationship` (`commandId`, `typeId`)');
        $this->addSql('
           INSERT IGNORE INTO `tc_command_types` (`commandId`, `typeId`)
              SELECT `command_id`, `type` FROM `tc_travels`;
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_command_types` DROP INDEX `unique_relationship`');
    }
}
