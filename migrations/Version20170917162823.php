<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


class Version20170917162823 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES(13, 'Převod z odd. pokladny', 'd', 1, '100', '0');");
        $this->addSql("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES(14, 'Převod do odd. pokladny', 'd', 2, '100', '0');");
        $this->addSql("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES(15, 'Převod z akce', 'a', 1, '100', '0');");
        $this->addSql("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES(16, 'Převod do akce', 'a', 2, '100', '0');");

        $this->addSql("CREATE TABLE `ac_chitsCategory_object` (" .
            "`categoryId` int(10) unsigned NOT NULL," .
            "`objectTypeId` varchar(20) COLLATE utf8_czech_ci NOT NULL," .
            "KEY `categoryId` (`categoryId`)," .
            "KEY `objectTypeId` (`objectTypeId`)," .
            "CONSTRAINT `ac_chitsCategory_object_ibfk_1` FOREIGN KEY (`categoryId`) REFERENCES `ac_chitsCategory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8_czech_ci;";

        $this->addSql("INSERT INTO `ac_chitsCategory_object` (`categoryId`, `objectTypeId`) VALUES" .
            "(1,'camp'),(2,'camp'), (3,'camp'), (4,'camp'), (5,'camp'), (6,'camp'), (7,'camp'), (8,'camp'), (9,'camp'), (10,'camp'), (11,'camp'), (12,'camp'), (13,'camp'), (14,'camp')," .
            "(1,'general'),(2,'general'),(3,'general'),(4,'general'),(5,'general'),(6,'general'),(7,'general'),(8,'general'),(9,'general'),(10,'general'),(11,'general'),(12,'general'),(13,'general'),(14,'general')," .
            "(2,'unit'),(3,'unit'),(4,'unit'),(5,'unit'),(6,'unit'),(7,'unit'),(8,'unit'),(9,'unit'),(10,'unit'),(12,'unit'),(13,'unit'),(15,'unit'),(16,'unit');");

    }

    public function down(Schema $schema)
    {
    }
}
