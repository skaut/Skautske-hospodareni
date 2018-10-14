<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181014120054 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
        CREATE TABLE ac_unit_cashbooks
        (
            id INT NOT NULL,
            unit_id CHAR(36) NOT NULL COMMENT '(DC2Type:unit_id)',
            year SMALLINT NOT NULL,
            cashbook_id VARCHAR(36) NOT NULL COMMENT '(DC2Type:cashbook_id)',
            INDEX IDX_1243558BF8BD700D (unit_id),
            PRIMARY KEY(id, unit_id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_czech_ci ENGINE = InnoDB
SQL
        );

        $this->addSql(<<<SQL
        CREATE TABLE ac_units
        (
            id CHAR(36) NOT NULL COMMENT '(DC2Type:unit_id)',
            active_cashbook_id INT NOT NULL,
            next_cashbook_id INT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_czech_ci ENGINE = InnoDB
SQL
        );

        $this->addSql(<<<SQL
            INSERT INTO ac_unit_cashbooks (unit_id, cashbook_id, year, id)
                SELECT
                    o.skautisId, o.id, 2018, 1
                FROM ac_object o
                JOIN ac_cashbook c ON o.id = c.id
                WHERE o.type = 'unit'
SQL
        );
        $this->addSql(
            'INSERT INTO ac_units (id, active_cashbook_id, next_cashbook_id) SELECT unit_id, 1, 2 FROM ac_unit_cashbooks'
        );

        $this->addSql('ALTER TABLE ac_unit_cashbooks ADD CONSTRAINT FK_1243558BF8BD700D FOREIGN KEY (unit_id) REFERENCES ac_units (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_unit_cashbooks DROP FOREIGN KEY FK_1243558BF8BD700D');
        $this->addSql('DROP TABLE ac_unit_cashbooks');
        $this->addSql('DROP TABLE ac_units');
    }
}
