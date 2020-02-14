<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200214114359 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Unify mapping between Doctrine and ac_* tables';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('DROP INDEX participantId_event_type ON ac_participants');
        $this->addSql(<<<SQL
            ALTER TABLE ac_participants
                CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:payment_id)',
                CHANGE participantId participantId INT UNSIGNED NOT NULL,
                CHANGE event_id event_id INT NOT NULL,
                CHANGE payment payment NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)',
                CHANGE repayment repayment NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)',
                CHANGE isAccount isAccount VARCHAR(255) NOT NULL
        SQL);
        $this->addSql('ALTER TABLE ac_chitsCategory_object DROP FOREIGN KEY ac_chitsCategory_object_ibfk_1');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE objectTypeId objectTypeId VARCHAR(20) NOT NULL COMMENT \'(DC2Type:string_enum)\', ADD PRIMARY KEY (categoryId, objectTypeId)');
        $this->addSql('ALTER TABLE ac_chitsCategory_object ADD CONSTRAINT FK_824C4F259C370B71 FOREIGN KEY (categoryId) REFERENCES ac_chitsCategory (id)');
        $this->addSql('ALTER TABLE ac_chitsCategory_object RENAME INDEX categoryid TO IDX_824C4F259C370B71');
        $this->addSql('ALTER TABLE ac_chits_item CHANGE price price DOUBLE PRECISION UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_scan DROP FOREIGN KEY FK_FEC2BFD22AEA3AE4');
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE chit_id chit_id INT UNSIGNED DEFAULT NULL');

        $this->addSql('DROP INDEX category ON ac_chits');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB792AEA3AE4');

        $this->addSql(<<<'SQL'
            ALTER TABLE ac_chits
                DROP x_purpose,
                DROP x_price,
                DROP x_priceText,
                DROP x_category,
                DROP x_category_operation_type,
                CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                CHANGE eventId eventId CHAR(36) DEFAULT NULL COMMENT '(DC2Type:cashbook_id)',
                CHANGE num num VARCHAR(5) DEFAULT NULL COMMENT '(DC2Type:chit_number)',
                CHANGE date date DATE NOT NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE recipient recipient VARCHAR(64) DEFAULT NULL COMMENT '(DC2Type:recipient)',
                CHANGE payment_method payment_method VARCHAR(13) NOT NULL COMMENT '(DC2Type:string_enum)'
        SQL);

        $this->addSql('ALTER TABLE ac_chit_scan ADD CONSTRAINT FK_FEC2BFD22AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id)');
        $this->addSql('ALTER TABLE ac_chits ADD CONSTRAINT FK_DBBC2DBC2B2EBB6C FOREIGN KEY (eventId) REFERENCES ac_cashbook (id)');
        $this->addSql('ALTER TABLE ac_chits RENAME INDEX eventid TO IDX_DBBC2DBC2B2EBB6C');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB79126F525E');
        $this->addSql('ALTER TABLE ac_chit_to_item CHANGE chit_id chit_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB79126F525E FOREIGN KEY (item_id) REFERENCES ac_chits_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB792AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id)  ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ac_chits DROP FOREIGN KEY FK_DBBC2DBC2B2EBB6C');
        $this->addSql('ALTER TABLE ac_cashbook CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_chits ADD CONSTRAINT FK_DBBC2DBC2B2EBB6C FOREIGN KEY (eventId) references ac_cashbook (id)');

        $this->addSql('ALTER TABLE ac_chitsCategory RENAME INDEX short TO UNIQ_43247D658F2890A2');
        $this->addSql('ALTER TABLE ac_unit_budget_category DROP FOREIGN KEY ac_unit_budget_category_ibfk_4');
        $this->addSql('ALTER TABLE ac_unit_budget_category DROP deleted');
        $this->addSql('ALTER TABLE ac_unit_budget_category ADD CONSTRAINT FK_356BCD1F10EE4CEE FOREIGN KEY (parentId) REFERENCES ac_unit_budget_category (id)');
        $this->addSql('ALTER TABLE ac_unit_budget_category RENAME INDEX parentid TO IDX_356BCD1F10EE4CEE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_cashbook CHANGE id id VARCHAR(36) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`');
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE chit_id chit_id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB792AEA3AE4');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB79126F525E');
        $this->addSql('ALTER TABLE ac_chit_to_item CHANGE chit_id chit_id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB792AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB79126F525E FOREIGN KEY (item_id) REFERENCES ac_chits_item (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE ac_chits DROP FOREIGN KEY FK_DBBC2DBC2B2EBB6C');
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_chits
                ADD x_purpose VARCHAR(120) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`,
                ADD x_price DOUBLE PRECISION DEFAULT NULL,
                ADD x_priceText VARCHAR(100) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`,
                ADD x_category INT UNSIGNED DEFAULT NULL,
                ADD x_category_operation_type VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci` COMMENT '(DC2Type:string_enum)',
                CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                CHANGE payment_method payment_method VARCHAR(13) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`,
                CHANGE num num VARCHAR(5) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`,
                CHANGE date date DATE NOT NULL,
                CHANGE recipient recipient VARCHAR(64) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`,
                CHANGE eventId eventId VARCHAR(36) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`
        SQL);

        $this->addSql('CREATE INDEX category ON ac_chits (x_category)');
        $this->addSql('ALTER TABLE ac_chits RENAME INDEX idx_dbbc2dbc2b2ebb6c TO eventId');
        $this->addSql('ALTER TABLE ac_chitsCategory RENAME INDEX UNIQ_43247D658F2890A2 TO short');
        $this->addSql('ALTER TABLE ac_chitsCategory_object DROP FOREIGN KEY FK_824C4F259C370B71');
        $this->addSql('ALTER TABLE ac_chitsCategory_object DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE objectTypeId objectTypeId VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`');
        $this->addSql('ALTER TABLE ac_chitsCategory_object ADD CONSTRAINT ac_chitsCategory_object_ibfk_1 FOREIGN KEY (categoryId) REFERENCES ac_chitsCategory (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ac_chitsCategory_object RENAME INDEX idx_824c4f259c370b71 TO categoryId');
        $this->addSql('ALTER TABLE ac_chits_item CHANGE price price DOUBLE PRECISION NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_participants
                CHANGE id id VARCHAR(36) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`,
                CHANGE participantId participantId INT UNSIGNED NOT NULL COMMENT 'ID',
                CHANGE payment payment DOUBLE PRECISION UNSIGNED NOT NULL,
                CHANGE repayment repayment DOUBLE PRECISION UNSIGNED NOT NULL,
                CHANGE isAccount isAccount VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`,
                CHANGE event_id event_id INT UNSIGNED DEFAULT NULL
        SQL);
        $this->addSql('CREATE UNIQUE INDEX participantId_event_type ON ac_participants (participantId, event_type)');
        $this->addSql('ALTER TABLE ac_unit_budget_category DROP FOREIGN KEY FK_356BCD1F10EE4CEE');
        $this->addSql('ALTER TABLE ac_unit_budget_category ADD deleted TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE ac_unit_budget_category ADD CONSTRAINT ac_unit_budget_category_ibfk_4 FOREIGN KEY (parentId) REFERENCES ac_unit_budget_category (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE ac_unit_budget_category RENAME INDEX idx_356bcd1f10ee4cee TO parentId');
    }
}
