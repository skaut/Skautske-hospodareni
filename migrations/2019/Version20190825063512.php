<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190825063512 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP INDEX unitId ON pa_smtp');
        $this->addSql(<<<SQL
            ALTER TABLE pa_smtp 
                CHANGE secure secure VARCHAR(64) NOT NULL COMMENT '(DC2Type:string_enum)',
                CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            ALTER TABLE pa_smtp 
                CHANGE secure secure VARCHAR(64) NOT NULL COLLATE utf8_czech_ci, 
                CHANGE created created DATETIME NOT NULL
        SQL);
        $this->addSql('CREATE INDEX unitId ON pa_smtp (unitId)');
    }
}
