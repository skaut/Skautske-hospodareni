<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170808142047 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_vehicle ADD subunit_id INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_vehicle DROP subunit_id');
        $this->addSql('CREATE INDEX unit_id ON tc_vehicle (unit_id)');
    }
}
