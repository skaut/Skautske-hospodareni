<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190824173018 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_commands DROP FOREIGN KEY tc_commands_ibfk_1');
        $this->addSql('ALTER TABLE tc_commands DROP FOREIGN KEY tc_commands_ibfk_2');
        $this->addSql('DROP INDEX contract_id ON tc_commands');
        $this->addSql(<<<SQL
            ALTER TABLE tc_commands 
                CHANGE fuel_price fuel_price NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)', 
                CHANGE amortization amortization NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)', 
                CHANGE closed closed DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);

        $this->addSql('ALTER TABLE tc_commands ADD CONSTRAINT FK_4D5B6D0C545317D1 FOREIGN KEY (vehicle_id) REFERENCES tc_vehicle (id)');
        $this->addSql('ALTER TABLE tc_commands RENAME INDEX vehicle_id TO IDX_4D5B6D0C545317D1');
        $this->addSql('ALTER TABLE tc_command_types DROP FOREIGN KEY tc_command_types_ibfk_5');
        $this->addSql('ALTER TABLE tc_command_types DROP FOREIGN KEY tc_command_types_ibfk_6');
        $this->addSql('DROP INDEX unique_relationship ON tc_command_types');
        $this->addSql(<<<SQL
            ALTER TABLE tc_command_types
                CHANGE typeId typeId VARCHAR(5) NOT NULL, ADD PRIMARY KEY (commandId, typeId)
        SQL);
        $this->addSql('ALTER TABLE tc_command_types ADD CONSTRAINT FK_DC7EBB8F36C645 FOREIGN KEY (commandId) REFERENCES tc_commands (id)');
        $this->addSql('ALTER TABLE tc_command_types ADD CONSTRAINT FK_DC7EBB9BF49490 FOREIGN KEY (typeId) REFERENCES tc_travelTypes (type)');
        $this->addSql('ALTER TABLE tc_command_types RENAME INDEX commandid TO IDX_DC7EBB8F36C645');
        $this->addSql('ALTER TABLE tc_command_types RENAME INDEX typeid TO IDX_DC7EBB9BF49490');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_command_types DROP INDEX `PRIMARY`, ADD UNIQUE INDEX unique_relationship (commandId, typeId)');
        $this->addSql('ALTER TABLE tc_command_types DROP FOREIGN KEY FK_DC7EBB8F36C645');
        $this->addSql('ALTER TABLE tc_command_types DROP FOREIGN KEY FK_DC7EBB9BF49490');
        $this->addSql(<<<SQL
            ALTER TABLE tc_command_types
                CHANGE typeId typeId VARCHAR(5) DEFAULT 'auv' NOT NULL COLLATE utf8_czech_ci
        SQL);
        $this->addSql('ALTER TABLE tc_command_types ADD CONSTRAINT tc_command_types_ibfk_5 FOREIGN KEY (commandId) REFERENCES tc_commands (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tc_command_types ADD CONSTRAINT tc_command_types_ibfk_6 FOREIGN KEY (typeId) REFERENCES tc_travelTypes (type) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tc_command_types RENAME INDEX idx_dc7ebb9bf49490 TO typeId');
        $this->addSql('ALTER TABLE tc_command_types RENAME INDEX idx_dc7ebb8f36c645 TO commandId');
        $this->addSql('ALTER TABLE tc_commands DROP FOREIGN KEY FK_4D5B6D0C545317D1');
        $this->addSql(<<<SQL
            ALTER TABLE tc_commands
                CHANGE fuel_price fuel_price DOUBLE PRECISION NOT NULL,
                CHANGE amortization amortization DOUBLE PRECISION NOT NULL,
                CHANGE closed closed DATETIME DEFAULT NULL,
                CHANGE unit unit VARCHAR(64) NOT NULL COLLATE utf8_czech_ci
        SQL);
        $this->addSql('ALTER TABLE tc_commands ADD CONSTRAINT tc_commands_ibfk_1 FOREIGN KEY (contract_id) REFERENCES tc_contracts (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE tc_commands ADD CONSTRAINT tc_commands_ibfk_2 FOREIGN KEY (vehicle_id) REFERENCES tc_vehicle (id) ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX contract_id ON tc_commands (contract_id)');
        $this->addSql('ALTER TABLE tc_commands RENAME INDEX idx_4d5b6d0c545317d1 TO vehicle_id');
    }
}
