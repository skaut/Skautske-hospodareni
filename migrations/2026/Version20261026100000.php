<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove obsolete Doctrine type comments from announcement and system role dates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement CHANGE published_at published_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE system_user_role CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement CHANGE published_at published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE system_user_role CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
