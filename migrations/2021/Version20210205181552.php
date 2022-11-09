<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210205181552 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_chitsCategory` 
                CHANGE `label` `name` varchar(64) NOT NULL,
                CHANGE `short` `shortcut` varchar(64) NOT NULL,
                CHANGE `type` `operation_type` varchar(64) NOT NULL COMMENT '(DC2Type:string_enum)',
                CHANGE `orderby` `priority` smallint NOT NULL
                ;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_chits` CHANGE `lock` `locked` int NULL;
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `tc_commands`
                CHANGE `passengers` `fellow_passengers` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL,
                CHANGE `closed` `closed_at` datetime NULL COMMENT '(DC2Type:datetime_immutable)';
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `tc_contracts`
                CHANGE `unit_person` `unit_representative` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL COMMENT 'jméno osoby zastupující jednotku',
                CHANGE `start` `since` date NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE `end` `until` date NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE `template` `template_version` smallint NOT NULL COMMENT '1-old, 2-podle NOZ';
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `pa_group`
                CHANGE `label` `name` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL,
                CHANGE `state_info` `note` varchar(250) COLLATE 'utf8_czech_ci' NOT NULL;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `log`
                CHANGE `unitId` `unit_id` int NOT NULL,
                CHANGE `userId` `user_id` int NOT NULL,
                CHANGE `type` `type` varchar(255) COLLATE 'utf8_czech_ci' NOT NULL COMMENT '(DC2Type:string_enum)',
                CHANGE `typeId` `type_id` int NULL;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_chitsCategory_object`
                CHANGE `categoryId` `category_id` int unsigned NOT NULL,
                CHANGE `objectTypeId` `type` varchar(20) COLLATE 'utf8_general_ci' NOT NULL COMMENT '(DC2Type:string_enum)';
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_participants`
                CHANGE `participantId` `participant_id` int NOT NULL AFTER `id`,
                CHANGE `isAccount` `account` varchar(255) COLLATE 'utf8_czech_ci' NOT NULL AFTER `repayment`;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `pa_payment`
                CHANGE `groupId` `group_id` int NOT NULL AFTER `id`,
                CHANGE `personId` `person_id` int NULL AFTER `email`,
                CHANGE `maturity` `due_date` date NOT NULL COMMENT '(DC2Type:chronos_date)' AFTER `amount`,
                CHANGE `vs` `variable_symbol` varchar(10) COLLATE 'utf8_czech_ci' NULL COMMENT '(DC2Type:variable_symbol)' AFTER `due_date`,
                CHANGE `ks` `constant_symbol` smallint NULL AFTER `variable_symbol`,
                CHANGE `dateClosed` `closed_at` datetime NULL COMMENT '(DC2Type:datetime_immutable)' AFTER `transactionId`,
                CHANGE `paidFrom` `bank_account` varchar(64) COLLATE 'utf8_czech_ci' NULL AFTER `closed_by_username`;;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `pa_group`
                CHANGE `maturity` `due_date` date NULL COMMENT '(DC2Type:chronos_date)' AFTER `amount`,
                CHANGE `ks` `constant_symbol` int NULL AFTER `due_date`,
                CHANGE `nextVs` `next_variable_symbol` varchar(255) COLLATE 'utf8_czech_ci' NULL COMMENT '(DC2Type:variable_symbol)' AFTER `constant_symbol`;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `tc_travels`
                CHANGE `type` `transport_type` varchar(255) COLLATE 'utf8_czech_ci' NOT NULL COMMENT '(DC2Type:string_enum)' AFTER `distance`;
        SQL);

        $this->addSql('ALTER TABLE ac_chitsCategory RENAME INDEX uniq_43247d658f2890a2 TO UNIQ_43247D652EF83F9C');
        $this->addSql('ALTER TABLE ac_chitsCategory RENAME INDEX orderby TO priority');
        $this->addSql('ALTER TABLE ac_chitsCategory_object RENAME INDEX idx_824c4f259c370b71 TO IDX_824C4F2512469DE2');
        $this->addSql('ALTER TABLE ac_chitsCategory_object RENAME INDEX objecttypeid TO type');
        $this->addSql('ALTER TABLE ac_participants RENAME INDEX actionid TO eventId');
        $this->addSql('ALTER TABLE ac_unit_budget_category RENAME INDEX objectid_year TO unitId_year');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_chitsCategory`
                CHANGE `name` `label` varchar(64) NOT NULL,
                CHANGE `shortcut` `short` varchar(64) NOT NULL,
                CHANGE `operation_type` `type` varchar(255) NOT NULL,
                CHANGE `priority` `orderby` smallint unsigned NOT NULL;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_chits` CHANGE `locked` `lock` int unsigned NULL;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `tc_commands`
                CHANGE `fellow_passengers` `passengers` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL,
                CHANGE `closed_at` `closed` datetime NULL COMMENT '(DC2Type:datetime_immutable)';
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `tc_contracts`
                CHANGE `unit_representative` `unit_person` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL COMMENT 'jméno osoby zastupující jednotku',
                CHANGE `since` `start` date NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE `until` `end` date NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE `template_version` `template` smallint NOT NULL COMMENT '1-old, 2-podle NOZ';
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `pa_group`
                CHANGE `name` `label` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL,
                CHANGE `note` `state_info` varchar(250) COLLATE 'utf8_czech_ci' NOT NULL;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `log`
                CHANGE `unit_id` `unitId` int unsigned NOT NULL AFTER `id`,
                CHANGE `user_id` `userId` int unsigned NOT NULL AFTER `date`,
                CHANGE `type_id` `typeId` int unsigned NULL AFTER `type`;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_chitsCategory_object`
                CHANGE `category_id` `categoryId` int unsigned NOT NULL,
                CHANGE `type` `objectTypeId` varchar(20) COLLATE 'utf8_general_ci' NOT NULL COMMENT '(DC2Type:string_enum)';
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_participants`
                CHANGE `participant_id` `participantId` int unsigned NOT NULL AFTER `id`,
                CHANGE `account` `isAccount` varchar(255) COLLATE 'utf8_czech_ci' NOT NULL AFTER `repayment`;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `pa_payment`
                CHANGE `group_id` `groupId` int unsigned NOT NULL AFTER `id`,
                CHANGE `person_id` `personId` int NULL AFTER `email`,
                CHANGE `due_date` `maturity` date NOT NULL COMMENT '(DC2Type:chronos_date)' AFTER `amount`,
                CHANGE `variable_symbol` `vs` varchar(10) COLLATE 'utf8_czech_ci' NULL COMMENT '(DC2Type:variable_symbol)' AFTER `maturity`,
                CHANGE `constant_symbol` `ks` smallint unsigned NULL AFTER `vs`,
                CHANGE `closed_at` `dateClosed` datetime NULL COMMENT '(DC2Type:datetime_immutable)' AFTER `transactionId`,
                CHANGE `bank_account` `paidFrom` varchar(64) COLLATE 'utf8_czech_ci' NULL AFTER `closed_by_username`;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `pa_group`
                CHANGE `due_date` `maturity` date NULL COMMENT '(DC2Type:chronos_date)' AFTER `amount`,
                CHANGE `constant_symbol` `ks` int unsigned NULL AFTER `maturity`,
                CHANGE `next_variable_symbol` `nextVs` varchar(255) COLLATE 'utf8_czech_ci' NULL COMMENT '(DC2Type:variable_symbol)' AFTER `ks`;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `tc_travels`
                CHANGE `transport_type` `type` varchar(255) COLLATE 'utf8_czech_ci' NOT NULL COMMENT '(DC2Type:string_enum)' AFTER `distance`;
        SQL);

        $this->addSql('ALTER TABLE ac_chitsCategory RENAME INDEX priority TO orderby');
        $this->addSql('ALTER TABLE ac_chitsCategory RENAME INDEX uniq_43247d652ef83f9c TO UNIQ_43247D658F2890A2');
        $this->addSql('ALTER TABLE ac_chitsCategory_object RENAME INDEX idx_824c4f2512469de2 TO IDX_824C4F259C370B71');
        $this->addSql('ALTER TABLE ac_chitsCategory_object RENAME INDEX type TO objectTypeId');
        $this->addSql('ALTER TABLE ac_participants RENAME INDEX eventid TO actionId');
        $this->addSql('ALTER TABLE ac_unit_budget_category RENAME INDEX unitid_year TO objectId_year');
    }
}
