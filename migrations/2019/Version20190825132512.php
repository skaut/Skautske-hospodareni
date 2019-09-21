<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190825132512 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE ac_chit_scan (
                id INT AUTO_INCREMENT NOT NULL,
                chit_id BIGINT(20) UNSIGNED NOT NULL, 
                file_path VARCHAR(255) NOT NULL COMMENT '(DC2Type:file_path)',
                INDEX IDX_FEC2BFD22AEA3AE4 (chit_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE ac_chit_scan ADD CONSTRAINT FK_FEC2BFD22AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE ac_chit_scan');
    }
}
