<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200101231338 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds tables for list of sent emails with payment';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE pa_payment_sent_emails (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                payment_id INT DEFAULT NULL,
                type VARCHAR(255) NOT NULL COMMENT '(DC2Type:string_enum)',
                time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                sender_name VARCHAR(255) NOT NULL,
                INDEX IDX_95359C6C4C3A3BB (payment_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE pa_payment_sent_emails ADD CONSTRAINT FK_95359C6C4C3A3BB FOREIGN KEY (payment_id) REFERENCES pa_payment (id)');
        $this->addSql(<<<SQL
            INSERT INTO pa_payment_sent_emails (payment_id, type, time, sender_name)
                SELECT id, 'payment_info', NOW(), 'Hospodaření' FROM pa_payment WHERE state = 'send'
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE pa_payment_sent_emails');
    }
}
