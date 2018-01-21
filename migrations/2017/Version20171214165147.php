<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171214165147 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE tc_vehicle ADD metadata_author_name VARCHAR(255) NOT NULL, ADD metadata_created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("UPDATE tc_vehicle SET metadata_author_name = 'Hospodaření', metadata_created_at = '2018-01-01'");
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE tc_vehicle DROP metadata_author_name, DROP metadata_created_at');
    }
}
