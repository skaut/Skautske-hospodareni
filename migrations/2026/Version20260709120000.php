<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user preference for remembered SkautIS role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_preference ADD remember_skautis_role TINYINT(1) DEFAULT 0 NOT NULL, ADD remembered_skautis_role_id INT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_preference DROP remember_skautis_role, DROP remembered_skautis_role_id');
    }
}
