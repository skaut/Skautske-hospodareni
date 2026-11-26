<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remember a Google account whose connection Google refused';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE google_oauth ADD invalid_since DATETIME DEFAULT NULL, ADD invalid_reason VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE google_oauth DROP invalid_since, DROP invalid_reason');
    }
}
