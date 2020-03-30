<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200330183259 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add separate price column for public transport travels';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tc_travels ADD price NUMERIC(8, 2) DEFAULT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('UPDATE tc_travels SET price = distance, distance = NULL WHERE has_fuel = 0');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('UPDATE tc_travels SET distance = price WHERE price IS NOT NULL');
        $this->addSql('ALTER TABLE tc_travels DROP price');
    }
}
