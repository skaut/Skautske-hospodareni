<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170707164123 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
CREATE TABLE `log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unitId` int(11) unsigned NOT NULL,
  `date` datetime NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
  `objectId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `unitId` (`unitId`),
  KEY `objectId` (`objectId`),
  CONSTRAINT `log_ibfk_1` FOREIGN KEY (`objectId`) REFERENCES `ac_object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        ");


    }

    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS `log`;");
    }
}
