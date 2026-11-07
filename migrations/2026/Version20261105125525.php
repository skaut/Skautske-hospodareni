<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105125525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the task queue processed by the cron (batches, their tasks and statistics of runs)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_queue_job (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, scope_type VARCHAR(50) NOT NULL, scope_id INT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, created_by_name VARCHAR(255) DEFAULT NULL, acknowledged_at DATETIME DEFAULT NULL, acknowledged_by_name VARCHAR(255) DEFAULT NULL, INDEX task_queue_job_scope_idx (scope_type, scope_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_queue_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, state VARCHAR(20) NOT NULL, payload JSON NOT NULL, label VARCHAR(255) NOT NULL, dedup_key VARCHAR(191) DEFAULT NULL, attempts SMALLINT UNSIGNED DEFAULT 0 NOT NULL, available_at DATETIME NOT NULL, claimed_at DATETIME DEFAULT NULL, claim_token VARCHAR(32) DEFAULT NULL, finished_at DATETIME DEFAULT NULL, message LONGTEXT DEFAULT NULL, job_id INT UNSIGNED NOT NULL, INDEX IDX_A6582647BE04EA9 (job_id), INDEX task_queue_item_state_idx (state, available_at), INDEX task_queue_item_job_state_idx (job_id, state), UNIQUE INDEX task_queue_item_dedup_key_uniq (dedup_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task_queue_item ADD CONSTRAINT FK_A6582647BE04EA9 FOREIGN KEY (job_id) REFERENCES task_queue_job (id) ON DELETE CASCADE');
        $this->addSql('CREATE TABLE task_queue_run (id INT UNSIGNED AUTO_INCREMENT NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, taken INT UNSIGNED DEFAULT 0 NOT NULL, done INT UNSIGNED DEFAULT 0 NOT NULL, skipped INT UNSIGNED DEFAULT 0 NOT NULL, failed INT UNSIGNED DEFAULT 0 NOT NULL, uncertain INT UNSIGNED DEFAULT 0 NOT NULL, postponed INT UNSIGNED DEFAULT 0 NOT NULL, recovered INT UNSIGNED DEFAULT 0 NOT NULL, stop_reason VARCHAR(20) DEFAULT NULL, pending_after INT UNSIGNED DEFAULT NULL, INDEX task_queue_run_started_at_idx (started_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_queue_run');
        $this->addSql('ALTER TABLE task_queue_item DROP FOREIGN KEY FK_A6582647BE04EA9');
        $this->addSql('DROP TABLE task_queue_item');
        $this->addSql('DROP TABLE task_queue_job');
    }
}
