<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170424092204 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('DELETE FROM tc_commands WHERE deleted = 1');
        $this->addSql('ALTER TABLE tc_commands DROP deleted');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE tc_commands ADD deleted TINYINT(1) DEFAULT '0' NOT NULL");
    }
}
