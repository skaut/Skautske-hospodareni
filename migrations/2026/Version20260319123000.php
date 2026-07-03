<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create persistent admin user allowlist storage';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->createSchemaManager()->tablesExist(['admin_user'])) {
            return;
        }

        $this->addSql("CREATE TABLE admin_user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX admin_user_user_id_unique (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        if (! $this->connection->createSchemaManager()->tablesExist(['admin_user'])) {
            return;
        }

        $this->addSql('DROP TABLE admin_user');
    }
}
