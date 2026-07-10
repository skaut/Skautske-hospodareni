<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move technical error report replies to a history table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE technical_error_report_reply (id INT UNSIGNED AUTO_INCREMENT NOT NULL, report_id INT UNSIGNED NOT NULL, message LONGTEXT NOT NULL, sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX technical_error_report_reply_report_sent_at_idx (report_id, sent_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE technical_error_report_reply ADD CONSTRAINT FK_B2C6C3894BD2A4C0 FOREIGN KEY (report_id) REFERENCES technical_error_report (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO technical_error_report_reply (report_id, message, sent_at) SELECT id, reply_message, reply_sent_at FROM technical_error_report WHERE reply_sent_at IS NOT NULL AND reply_message IS NOT NULL');
        $this->addSql('ALTER TABLE technical_error_report DROP reply_sent_at, DROP reply_message');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE technical_error_report ADD reply_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reply_message LONGTEXT DEFAULT NULL');
        $this->addSql('UPDATE technical_error_report r SET reply_sent_at = (SELECT rr.sent_at FROM technical_error_report_reply rr WHERE rr.report_id = r.id ORDER BY rr.sent_at DESC, rr.id DESC LIMIT 1), reply_message = (SELECT rr.message FROM technical_error_report_reply rr WHERE rr.report_id = r.id ORDER BY rr.sent_at DESC, rr.id DESC LIMIT 1)');
        $this->addSql('DROP TABLE technical_error_report_reply');
    }
}
