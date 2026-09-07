<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the admin allowlist with assignable system roles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE system_user_role (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, role VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX system_user_role_user_role_unique (user_id, role), INDEX system_user_role_user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");

        if ($this->connection->createSchemaManager()->tablesExist(['admin_user'])) {
            $this->addSql("INSERT IGNORE INTO system_user_role (user_id, role, created_at) SELECT user_id, 'admin', created_at FROM admin_user");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE system_user_role');
    }
}
