<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Count usage as sessions by unit and section, keep logins without the user and drop the page view counters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE usage_session_daily (id INT UNSIGNED AUTO_INCREMENT NOT NULL, day DATE NOT NULL, unit_code VARCHAR(32) NOT NULL, section VARCHAR(32) NOT NULL, screen VARCHAR(64) NOT NULL, page VARCHAR(191) NOT NULL, sessions INT UNSIGNED NOT NULL, UNIQUE INDEX usage_session_daily_uniq (day, unit_code, section, screen, page), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        // Logins keep only a keyed hash of the user, no unit and no role. The rows kept so far name the user,
        // so they are dropped; the overview of logins starts again from this deploy.
        $this->addSql('DELETE FROM user_login');
        $this->addSql('DROP INDEX user_login_user_idx ON user_login');
        $this->addSql('DROP INDEX user_login_unit_idx ON user_login');
        $this->addSql('ALTER TABLE user_login ADD user_hash CHAR(64) NOT NULL, ADD dark_mode TINYINT(1) DEFAULT NULL, DROP user_id, DROP unit_id, DROP role_id, DROP role_key');
        $this->addSql('CREATE INDEX user_login_user_idx ON user_login (user_hash, logged_in_at)');
        // Page views are replaced by the sessions above; the old counts are not comparable and are not kept.
        $this->addSql('DROP TABLE page_view_daily');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE page_view_daily (id INT UNSIGNED AUTO_INCREMENT NOT NULL, page_key VARCHAR(191) NOT NULL, day DATE NOT NULL, views INT UNSIGNED NOT NULL, INDEX page_view_daily_day_idx (day), UNIQUE INDEX page_view_daily_page_day_uniq (page_key, day), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        $this->addSql('DELETE FROM user_login');
        $this->addSql('DROP INDEX user_login_user_idx ON user_login');
        $this->addSql('ALTER TABLE user_login ADD user_id INT UNSIGNED NOT NULL, ADD unit_id INT UNSIGNED DEFAULT NULL, ADD role_id INT UNSIGNED DEFAULT NULL, ADD role_key VARCHAR(64) DEFAULT NULL, DROP user_hash, DROP dark_mode');
        $this->addSql('CREATE INDEX user_login_user_idx ON user_login (user_id, logged_in_at)');
        $this->addSql('CREATE INDEX user_login_unit_idx ON user_login (unit_id, logged_in_at)');
        $this->addSql('DROP TABLE usage_session_daily');
    }
}
