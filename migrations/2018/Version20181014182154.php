<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181014182154 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_unit_budget_category DROP FOREIGN KEY ac_unit_budget_category_ibfk_2');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE objectId unit_id INT(11)');
        $this->addSql('UPDATE ac_unit_budget_category c JOIN ac_object o ON o.id = c.unit_id SET c.unit_id = o.skautisId');

        $this->addSql('ALTER TABLE ac_chits DROP FOREIGN KEY ac_chits_ibfk_1');
        $this->addSql('ALTER TABLE ac_chits CHANGE eventId eventId VARCHAR(36)');
        $this->addSql('ALTER TABLE ac_object CHANGE id id VARCHAR(36)');
        $this->addSql('ALTER TABLE ac_cashbook CHANGE id id VARCHAR(36)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE ac_unit_budget_category c JOIN ac_object o ON o.skautisId = c.unit_id SET c.unit_id = o.id');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE unit_id objectId INT(11)');
        $this->addSql('ALTER TABLE ac_unit_budget_category ADD CONSTRAINT ac_unit_budget_category_ibfk_2 FOREIGN KEY (objectId) REFERENCES ac_object (id)');

        $this->addSql('ALTER TABLE ac_chits CHANGE eventId eventId BIGINT(20) UNSIGNED');
        $this->addSql('ALTER TABLE ac_object CHANGE id id INT(11)');
        $this->addSql('ALTER TABLE ac_cashbook CHANGE id id INT(11)');
    }
}
