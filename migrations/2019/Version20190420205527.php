<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190420205527 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_group DROP FOREIGN KEY pa_group_ibfk_1');
        $this->addSql('DROP TABLE pa_group_type');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE `pa_group_type` (
              `id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
        SQL);

        $this->addSql("INSERT INTO `pa_group_type` (`id`) VALUES ('camp'), ('registration')");

        $this->addSql(<<<'SQL'
            ALTER TABLE pa_group
                ADD CONSTRAINT `pa_group_ibfk_1` FOREIGN KEY (`groupType`)
                    REFERENCES `pa_group_type` (`id`) ON UPDATE CASCADE
        SQL);
    }
}
