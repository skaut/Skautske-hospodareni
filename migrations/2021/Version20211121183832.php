<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211121183832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove nullability from columns that do not need it';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE chit_id chit_id INT NOT NULL');
        $this->addSql('ALTER TABLE ac_chits CHANGE eventId eventId CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE unit_id unit_id INT NOT NULL');
        $this->addSql('ALTER TABLE pa_group_email CHANGE group_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE pa_group_unit CHANGE group_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE pa_payment_email_recipients CHANGE payment_id payment_id INT NOT NULL');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE payment_id payment_id INT NOT NULL');
        $this->addSql('ALTER TABLE tc_commands CHANGE place place VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE vehicle_id vehicle_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE chit_id chit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ac_chits CHANGE eventId eventId CHAR(36) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci` COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE unit_id unit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_group_email CHANGE group_id group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_group_unit CHANGE group_id group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment_email_recipients CHANGE payment_id payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE payment_id payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tc_commands CHANGE place place VARCHAR(64) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`');
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE vehicle_id vehicle_id INT DEFAULT NULL');
    }
}
