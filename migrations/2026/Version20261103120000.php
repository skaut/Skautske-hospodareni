<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store pausing, schedule and the last run of cron jobs for the administration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE cron_job (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, schedule VARCHAR(100) DEFAULT NULL, paused TINYINT(1) DEFAULT 0 NOT NULL, updated_at DATETIME DEFAULT NULL, updated_by_name VARCHAR(255) DEFAULT NULL, last_started_at DATETIME DEFAULT NULL, last_finished_at DATETIME DEFAULT NULL, last_result VARCHAR(20) DEFAULT NULL, last_error LONGTEXT DEFAULT NULL, UNIQUE INDEX cron_job_name_uniq (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cron_job');
    }
}
