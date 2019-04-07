<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190407182157 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_object DROP FOREIGN KEY ac_object_ibfk_2');
        $this->addSql('DROP TABLE ac_object_type');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(
            'CREATE TABLE `ac_object_type` ('
            . '`id` varchar(20) COLLATE utf8_czech_ci NOT NULL,'
            . ' PRIMARY KEY (`id`) '
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;'
        );

        $this->addSql("INSERT INTO `ac_object_type` (`id`) VALUES ('camp'), ('general'), ('unit')");
        $this->addSql(
            'ALTER TABLE hskauting.ac_object ADD CONSTRAINT `ac_object_ibfk_2` '
            . 'FOREIGN KEY (`type`) REFERENCES `ac_object_type` (`id`) ON UPDATE CASCADE'
        );
    }
}
