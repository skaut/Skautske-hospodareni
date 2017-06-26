<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


class Version20170623193150 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE pa_bank_account (id INT AUTO_INCREMENT NOT NULL, unit_id INT NOT NULL, name VARCHAR(255) NOT NULL, token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', allowed_for_subunits TINYINT(1) NOT NULL, number_prefix VARCHAR(6) DEFAULT NULL, number_number VARCHAR(10) NOT NULL, number_bank_code VARCHAR(4) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pa_group ADD bank_account_id INT DEFAULT NULL');
    }


    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE pa_bank_account');
        $this->addSql('ALTER TABLE pa_group DROP bank_account_id');
    }

}
