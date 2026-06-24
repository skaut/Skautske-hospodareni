<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invoice early access allowlist and request storage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE invoice_access_user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX invoice_access_user_user_id_unique (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE invoice_access_request (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, unit_id INT UNSIGNED DEFAULT NULL, role_id INT UNSIGNED DEFAULT NULL, display_name VARCHAR(255) NOT NULL, note LONGTEXT NOT NULL, state VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX invoice_access_request_user_state_idx (user_id, state), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE invoice_access_request');
        $this->addSql('DROP TABLE invoice_access_user');
    }
}
