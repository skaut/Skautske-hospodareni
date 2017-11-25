<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171121192634 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE ac_cashbook (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO ac_cashbook (id) SELECT id FROM ac_object');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE ac_cashbook');
    }
}
