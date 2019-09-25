<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170430153958 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_travels ADD has_fuel TINYINT(1) NOT NULL');
        $this->addSql('UPDATE tc_travels INNER JOIN tc_travelTypes AS types ON tc_travels.type = types.type SET tc_travels.has_fuel = types.hasFuel');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_travels DROP has_fuel');
    }
}
