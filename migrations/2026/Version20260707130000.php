<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user preference for background SkautIS login extension';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_preference ADD extend_skautis_login TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_preference DROP extend_skautis_login');
    }
}
