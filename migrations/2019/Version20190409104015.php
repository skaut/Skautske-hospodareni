<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190409104015 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(
            'CREATE TABLE `ac_chits_item` (' .
            '`_id` int(10) unsigned NOT NULL AUTO_INCREMENT,' .
            '`id` int(10) unsigned NOT NULL,' .
            '`chit_id` bigint(20) unsigned NOT NULL,' .
            '`price` float(9,2) NOT NULL,' .
            '`priceText` varchar(100) COLLATE utf8_czech_ci NOT NULL,' .
            '`category` int(10) unsigned NOT NULL,' .
            '`category_operation_type` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL COMMENT \'(DC2Type:string_enum)\',' .
            'PRIMARY KEY (`_id`),' .
            'KEY `chit_id` (`chit_id`),' .
            'CONSTRAINT `ac_chits_item_ibfk_2` FOREIGN KEY (`chit_id`) REFERENCES `ac_chits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;'
        );

        // move current data
        $this->addSql(
            'INSERT INTO `ac_chits_item` (`id`,`chit_id`, `price`, `priceText`, `category`, `category_operation_type`)' .
            'SELECT 1, `id`, `price`, `priceText`, `category`, `category_operation_type`' .
            'FROM `ac_chits`'
        );

        // temporary rename of origin column and default values
        $this->addSql(
            'ALTER TABLE `ac_chits`' .
            'CHANGE `price` `x_price` float(9,2) NULL AFTER `purpose`,' .
            'CHANGE `priceText` `x_priceText` varchar(100) COLLATE \'utf8_czech_ci\' NULL AFTER `x_price`,' .
            'CHANGE `category` `x_category` int(10) unsigned NULL AFTER `x_priceText`,' .
            'CHANGE `category_operation_type` `x_category_operation_type` varchar(255) COLLATE \'utf8_czech_ci\' NULL COMMENT \'(DC2Type:string_enum)\' AFTER `lock`;'
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(
            'ALTER TABLE `ac_chits`' .
            'CHANGE `x_price` `price` float(9,2) NOT NULL AFTER `purpose`,' .
            'CHANGE `x_priceText` `priceText` varchar(100) COLLATE \'utf8_czech_ci\' NOT NULL AFTER `price`,' .
            'CHANGE `x_category` `category` int(10) unsigned NOT NULL AFTER `priceText`,' .
            'CHANGE `x_category_operation_type` `category_operation_type` varchar(255) COLLATE \'utf8_czech_ci\' NULL COMMENT \'(DC2Type:string_enum)\' AFTER `lock`;'
        );

        $this->addSql('DROP TABLE IF EXISTS `ac_chits_item`;');
    }
}
