<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow chit numbers up to 6 characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_chits CHANGE num num VARCHAR(6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Selže, pokud už některý doklad šestiznakové číslo má – data se nezkracují potichu.
        $this->addSql('ALTER TABLE ac_chits CHANGE num num VARCHAR(5) DEFAULT NULL');
    }
}
