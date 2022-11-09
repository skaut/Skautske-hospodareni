<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210109150939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove pa_smtp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE pa_smtp');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE pa_smtp (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                unitId INT UNSIGNED NOT NULL,
                host VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`,
                username VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`,
                password VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`,
                secure VARCHAR(64) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci` COMMENT '(DC2Type:string_enum)',
                sender VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_czech_ci`,
                created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = ''
SQL);
    }
}
