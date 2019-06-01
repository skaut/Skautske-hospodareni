<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190601191610 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
        CREATE OR REPLACE VIEW `ac_chitsView` AS
        SELECT `ch`.`id` AS `chit_id`,`ch`.`eventId` AS `eventId`,`ch`.`recipient` AS `recipient`,`ch`.`num` AS `num`,`ch`.`date` AS `date`,
               `ch`.`lock` AS `lock`,`ch`.`payment_method` AS `payment_method`,`ci`.`id` AS `id`,`ci`.`purpose` AS `purpose`,
               `ci`.`price` AS `price`,`ci`.`priceText` AS `priceText`,`ci`.`category` AS `category`,`ci`.`category_operation_type` AS `category_operation_type`
        FROM `ac_chits` `ch` 
        LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id` 
        LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`;
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP VIEW `ac_chitsView`');
    }
}
