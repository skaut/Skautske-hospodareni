<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170430153958 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tc_travels ADD has_fuel TINYINT(1) NOT NULL");
        $this->addSql("UPDATE travels SET travels.has_fuel = types.hasFuel FROM tc_travels as travels INNER JOIN tc_travelTypes as types ON travels.type = types.type");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tc_travels DROP has_fuel");
    }

}
