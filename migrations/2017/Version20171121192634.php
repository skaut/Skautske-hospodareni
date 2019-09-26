<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171121192634 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE ac_cashbook (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO ac_cashbook (id) SELECT id FROM ac_object');
        $this->addSql("UPDATE ac_chits SET num = NULL WHERE num = ''");
        $this->addSql("UPDATE ac_chits SET recipient = NULL WHERE recipient = ''");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE ac_cashbook');
    }
}
