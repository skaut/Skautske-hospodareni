<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200724125509 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
        CREATE TABLE google_oauth (
            id CHAR(36) NOT NULL COMMENT '(DC2Type:ouath_id)',
            unit_id CHAR(36) NOT NULL COMMENT '(DC2Type:unit_id)',
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX unitid_email (unit_id, email), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE oauth');
    }
}
