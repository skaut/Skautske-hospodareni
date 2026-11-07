<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105140704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the days of automatic payment reminders and deploy their cron job paused';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_group ADD reminder_days_after_due INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment ADD automatic_reminder_at DATETIME DEFAULT NULL');
        // Automatic reminders start sending e-mails, so an administrator resumes the job in Admin → Cron.
        $this->addSql("INSERT INTO cron_job (name, paused) VALUES ('paymentReminders', 1) ON DUPLICATE KEY UPDATE paused = 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM cron_job WHERE name = 'paymentReminders'");
        $this->addSql('ALTER TABLE pa_group DROP reminder_days_after_due');
        $this->addSql('ALTER TABLE pa_payment DROP automatic_reminder_at');
    }
}
