<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180115183106 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE pa_group_email (id INT AUTO_INCREMENT NOT NULL, group_id INT UNSIGNED DEFAULT NULL, type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\', template_subject VARCHAR(255) NOT NULL, template_body LONGTEXT NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_7A67EADBFE54D947 (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pa_group_email ADD CONSTRAINT FK_7A67EADBFE54D947 FOREIGN KEY (group_id) REFERENCES pa_group (id)');

        $this->addSql("
            INSERT INTO pa_group_email (group_id, template_subject, template_body, type, enabled)
            SELECT id, email_template_subject, email_template_body, 'payment_info', 1 FROM pa_group
        ");

        $this->addSql('ALTER TABLE pa_group DROP email_template_body, DROP email_template_subject');
    }

    public function down(Schema $schema) : void
    {
    }
}
