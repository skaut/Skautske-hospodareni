<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190510135230 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE tc_vehicle_roadworthy_scan (
                id INT AUTO_INCREMENT NOT NULL,
                vehicle_id INT UNSIGNED DEFAULT NULL,
                file_path VARCHAR(255) NOT NULL,
                INDEX IDX_270D2917545317D1 (vehicle_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            ALTER TABLE tc_vehicle_roadworthy_scan
                ADD CONSTRAINT FK_270D2917545317D1
                    FOREIGN KEY (vehicle_id) REFERENCES tc_vehicle (id)
        SQL);
        $this->addSql('DROP INDEX unit_id ON tc_vehicle');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE tc_vehicle_roadworthy_scan');
    }
}
