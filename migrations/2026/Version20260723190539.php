<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Normalizuje DC2Type komentáře enum sloupců z původního `string_enum` na dedikované typy
 * (viz App\Model\Infrastructure\Types\*). Runtime chování se nemění – sloupce zůstávají VARCHAR
 * se stejnými hodnotami; mění se jen metadata komentáře, aby DB odpovídala ORM mapování.
 */
final class Version20260723190539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize enum column DC2Type comments (string_enum -> dedicated types)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_cashbook CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:cashbook_type)\'');
        $this->addSql('ALTER TABLE ac_chits CHANGE payment_method payment_method VARCHAR(13) NOT NULL COMMENT \'(DC2Type:chit_payment_method)\'');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE operation_type operation_type VARCHAR(64) NOT NULL COMMENT \'(DC2Type:cashbook_operation)\'');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE type type VARCHAR(20) NOT NULL COMMENT \'(DC2Type:cashbook_object_type)\'');
        $this->addSql('ALTER TABLE ac_chits_item CHANGE category_operation_type category_operation_type VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:cashbook_operation)\'');
        $this->addSql('ALTER TABLE ac_participants CHANGE event_type event_type VARCHAR(9) NOT NULL COMMENT \'(DC2Type:participant_event_type)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:cashbook_operation)\'');
        $this->addSql('ALTER TABLE log CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:log_type)\'');
        $this->addSql('ALTER TABLE pa_group CHANGE groupType groupType VARCHAR(20) DEFAULT NULL COMMENT \'typ entity(DC2Type:payment_group_type)\'');
        $this->addSql('ALTER TABLE pa_group_email CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:payment_email_type)\'');
        $this->addSql('ALTER TABLE pa_payment CHANGE state state VARCHAR(20) NOT NULL COMMENT \'(DC2Type:payment_state)\'');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:payment_email_type)\'');
        $this->addSql('ALTER TABLE tc_travels CHANGE transport_type transport_type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:travel_transport_type)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_cashbook CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE ac_chits CHANGE payment_method payment_method VARCHAR(13) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE operation_type operation_type VARCHAR(64) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE type type VARCHAR(20) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE ac_chits_item CHANGE category_operation_type category_operation_type VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE ac_participants CHANGE event_type event_type VARCHAR(9) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE log CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE pa_group CHANGE groupType groupType VARCHAR(20) DEFAULT NULL COMMENT \'typ entity(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE pa_group_email CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE pa_payment CHANGE state state VARCHAR(20) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE tc_travels CHANGE transport_type transport_type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
    }
}
