<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align announcement date columns with Doctrine mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement CHANGE published_at published_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement CHANGE published_at published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
