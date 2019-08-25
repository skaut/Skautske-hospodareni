<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190825051352 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_payment DROP FOREIGN KEY pa_payment_ibfk_1');
        $this->addSql('DROP TABLE pa_payment_state');
        $this->addSql('DROP INDEX groupId ON pa_payment');
        $this->addSql('DROP INDEX state ON pa_payment');
        $this->addSql(<<<SQL
            ALTER TABLE pa_payment 
                CHANGE state state VARCHAR(20) NOT NULL COMMENT '(DC2Type:string_enum)', 
                CHANGE maturity maturity DATE NOT NULL COMMENT '(DC2Type:chronos_date)', 
                CHANGE vs vs VARCHAR(10) DEFAULT NULL COMMENT '(DC2Type:variable_symbol)', 
                CHANGE transactionId transactionId INT UNSIGNED DEFAULT NULL,
                CHANGE dateClosed dateClosed DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE pa_payment_state (
                id VARCHAR(20) NOT NULL COLLATE utf8_czech_ci, 
                label VARCHAR(64) NOT NULL COLLATE utf8_czech_ci, 
                orderby TINYINT(1) NOT NULL, PRIMARY KEY(id)
              ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE pa_payment 
                CHANGE maturity maturity DATE NOT NULL, 
                CHANGE vs vs VARCHAR(10) DEFAULT NULL COLLATE utf8_czech_ci, 
                CHANGE dateClosed dateClosed DATETIME DEFAULT NULL, 
                CHANGE state state VARCHAR(20) NOT NULL COLLATE utf8_czech_ci, 
                CHANGE transactionId transactionId BIGINT UNSIGNED DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE pa_payment ADD CONSTRAINT pa_payment_ibfk_1 FOREIGN KEY (state) REFERENCES pa_payment_state (id) ON UPDATE CASCADE');
        $this->addSql('CREATE INDEX groupId ON pa_payment (groupId)');
        $this->addSql('CREATE INDEX state ON pa_payment (state)');
    }
}
