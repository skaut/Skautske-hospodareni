<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fuel type to vehicles, existing vehicles become combustion vehicles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tc_vehicle ADD fuel_type VARCHAR(16) DEFAULT 'combustion' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tc_vehicle DROP fuel_type');
    }
}
