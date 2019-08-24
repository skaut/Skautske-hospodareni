<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190824171748 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_travels DROP FOREIGN KEY tc_travels_ibfk_1');
        $this->addSql('ALTER TABLE tc_travels DROP FOREIGN KEY tc_travels_ibfk_3');
        $this->addSql('DROP INDEX type ON tc_travels');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travels
                CHANGE id id INT UNSIGNED NOT NULL,
                CHANGE start_date start_date DATE NOT NULL COMMENT '(DC2Type:chronos_date)', 
                CHANGE distance distance DOUBLE PRECISION UNSIGNED DEFAULT NULL,
                CHANGE has_fuel has_fuel SMALLINT NOT NULL
        SQL);
        $this->addSql('ALTER TABLE tc_travels ADD CONSTRAINT FK_F53E53633E1689A FOREIGN KEY (command_id) REFERENCES tc_commands (id)');
        $this->addSql('ALTER TABLE tc_travels RENAME INDEX tc_id TO IDX_F53E53633E1689A');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travelTypes 
                CHANGE hasFuel hasFuel TINYINT(1) DEFAULT '0' NOT NULL,
                CHANGE `order` `order` SMALLINT DEFAULT 10 NOT NULL
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travelTypes 
                CHANGE hasFuel hasFuel TINYINT(1) DEFAULT '0' NOT NULL,
                CHANGE `order` `order` TINYINT(1) DEFAULT '10' NOT NULL
        SQL);
        $this->addSql('ALTER TABLE tc_travels DROP FOREIGN KEY FK_F53E53633E1689A');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travels 
                CHANGE id id BIGINT UNSIGNED NOT NULL,
                CHANGE start_date start_date DATE NOT NULL,
                CHANGE has_fuel has_fuel TINYINT(1) NOT NULL,
                CHANGE distance distance DOUBLE PRECISION UNSIGNED NOT NULL
        SQL);
        $this->addSql('ALTER TABLE tc_travels ADD CONSTRAINT tc_travels_ibfk_1 FOREIGN KEY (command_id) REFERENCES tc_commands (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tc_travels ADD CONSTRAINT tc_travels_ibfk_3 FOREIGN KEY (type) REFERENCES tc_travelTypes (type) ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX type ON tc_travels (type)');
        $this->addSql('ALTER TABLE tc_travels RENAME INDEX idx_f53e53633e1689a TO tc_id');
    }
}
