<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record logins so the administration can report on system usage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE user_login (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, unit_id INT UNSIGNED DEFAULT NULL, role_id INT UNSIGNED DEFAULT NULL, role_key VARCHAR(64) DEFAULT NULL, logged_in_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_seen_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', logged_out_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', end_reason VARCHAR(16) DEFAULT NULL, device_type VARCHAR(16) NOT NULL, browser VARCHAR(32) NOT NULL, browser_version VARCHAR(16) DEFAULT NULL, platform VARCHAR(32) NOT NULL, INDEX user_login_logged_in_idx (logged_in_at), INDEX user_login_user_idx (user_id, logged_in_at), INDEX user_login_unit_idx (unit_id, logged_in_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_login');
    }
}
