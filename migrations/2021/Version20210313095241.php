<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210313095241 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
        CREATE VIEW `report_chit_items` AS
        SELECT `ch`.`id` AS `chit_id`,`ch`.`eventId` AS `eventId`,`ch`.`recipient` AS `recipient`,`ch`.`num` AS `num`,`ch`.`date` AS `date`,
               `ch`.`payment_method` AS `payment_method`,`ci`.`id` AS `id`,`ci`.`purpose` AS `purpose`,
               `ci`.`price` AS `price`,`ci`.`priceText` AS `priceText`,`ci`.`category` AS `category`,`ci`.`category_operation_type` AS `category_operation_type`
        FROM `ac_chits` `ch` 
        LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id` 
        LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`;
        SQL);

        $this->addSql(<<<'SQL'
        CREATE VIEW `report_cashbooks` AS
        SELECT c.type as Type,
                YEAR(`ch`.`date`) AS `Year`,
                COUNT(DISTINCT `ch`.`eventId`) AS `count of cashbooks`,
                ROUND(SUM(`ci`.`price` )) AS `Total amount`
        FROM `ac_chits` `ch` 
        LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id` 
        LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`
        LEFT JOIN `ac_cashbook` `c` ON `ch`.`eventId` = `c`.`id`
        GROUP BY Type, Year
        ORDER BY Type, Year DESC 
        SQL);

        $this->addSql(<<<'SQL'
        CREATE VIEW `report_cashbooks_amount` AS
        SELECT c.type as Type,
                YEAR(`ch`.`date`) AS `Year`,
                `ci`.`category_operation_type` as `Operational type`,
                ROUND(SUM(`ci`.`price` )) AS `Amount`
        FROM `ac_chits` `ch` 
        LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id` 
        LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`
        LEFT JOIN `ac_cashbook` `c` ON `ch`.`eventId` = `c`.`id`
        GROUP BY Type, Year, `ci`.`category_operation_type`
        ORDER BY Type, Year DESC
        SQL);

        $this->addSql(<<<'SQL'
        CREATE VIEW `report_payment_groups` AS
        SELECT g.groupType AS Type,
            YEAR(p.due_date) AS Year, 
            COUNT(DISTINCT g.id) AS 'Count of groups'
        FROM `pa_payment` p
        LEFT JOIN pa_group g ON g.id = p.group_id
        WHERE  p.state != 'canceled'
        GROUP BY Year, Type
        ORDER BY Type, Year DESC
        SQL);

        $this->addSql(<<<'SQL'
        CREATE VIEW `report_payment_groups_amounts` AS
        SELECT g.groupType AS Type,
            YEAR(p.due_date) AS Year, 
            p.state AS 'Status',
            ROUND(SUM(p.amount)) AS 'Total amount'
        FROM `pa_payment` p
        LEFT JOIN pa_group g ON g.id = p.group_id
        GROUP BY Year, Type, p.state
        ORDER BY Type, Year DESC
        SQL);

        $this->addSql('DROP VIEW IF EXISTS `ac_chitsView`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS `report_chit_items`');
        $this->addSql('DROP VIEW IF EXISTS `report_cashbooks`');
    }
}
