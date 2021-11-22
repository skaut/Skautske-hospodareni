<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211121192425 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
                CREATE TABLE ac_education_cashbooks (
                    id CHAR(36) NOT NULL COMMENT '(DC2Type:skautis_education_id)', 
                    cashbook_id CHAR(36) NOT NULL COMMENT '(DC2Type:cashbook_id)',
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ac_education_cashbooks');
    }
}
