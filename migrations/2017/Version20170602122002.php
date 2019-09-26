<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170602122002 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_travels MODIFY id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE tc_travels DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tc_travels ADD PRIMARY KEY (id, command_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_travels DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE tc_travels CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE tc_travels ADD PRIMARY KEY (id)');
    }
}
