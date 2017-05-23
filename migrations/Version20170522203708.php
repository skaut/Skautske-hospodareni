<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170522203708 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `ac_chits` CHANGE `purpose` `purpose` varchar(120) COLLATE 'utf8_czech_ci' NOT NULL AFTER `recipient`");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
