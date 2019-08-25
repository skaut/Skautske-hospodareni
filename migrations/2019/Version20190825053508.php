<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190825053508 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_group DROP FOREIGN KEY pa_group_ibfk_4');
        $this->addSql('DROP TABLE pa_group_state');
        $this->addSql('ALTER TABLE pa_group DROP FOREIGN KEY fk_bank_account_id');
        $this->addSql('ALTER TABLE pa_group DROP FOREIGN KEY pa_group_ibfk_6');
        $this->addSql('DROP INDEX smtp_id ON pa_group');
        $this->addSql('DROP INDEX groupType ON pa_group');
        $this->addSql('DROP INDEX state ON pa_group');
        $this->addSql('DROP INDEX fk_bank_account_id ON pa_group');
        $this->addSql('DROP INDEX objectId ON pa_group');
        $this->addSql(<<<SQL
            ALTER TABLE pa_group 
                CHANGE groupType groupType VARCHAR(20) DEFAULT NULL COMMENT 'typ entity(DC2Type:string_enum)', 
                CHANGE amount amount DOUBLE PRECISION DEFAULT NULL,
                CHANGE maturity maturity DATE DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                CHANGE nextVs nextVs VARCHAR(255) DEFAULT NULL COMMENT '(DC2Type:variable_symbol)',
                CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                CHANGE last_pairing last_pairing DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('ALTER TABLE pa_group_unit CHANGE group_id group_id INT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE pa_group_state (
                id VARCHAR(20) NOT NULL COLLATE utf8_czech_ci, 
                label VARCHAR(64) NOT NULL COLLATE utf8_czech_ci, 
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE pa_group 
                CHANGE groupType groupType VARCHAR(20) DEFAULT NULL COLLATE utf8_czech_ci COMMENT 'typ entity',
                CHANGE amount amount DOUBLE PRECISION UNSIGNED DEFAULT NULL,
                CHANGE maturity maturity DATE DEFAULT NULL,
                CHANGE nextVs nextVs INT UNSIGNED DEFAULT NULL,
                CHANGE created_at created_at DATETIME DEFAULT NULL,
                CHANGE last_pairing last_pairing DATETIME DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE pa_group ADD CONSTRAINT fk_bank_account_id FOREIGN KEY (bank_account_id) REFERENCES pa_bank_account (id)');
        $this->addSql('ALTER TABLE pa_group ADD CONSTRAINT pa_group_ibfk_4 FOREIGN KEY (state) REFERENCES pa_group_state (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE pa_group ADD CONSTRAINT pa_group_ibfk_6 FOREIGN KEY (smtp_id) REFERENCES pa_smtp (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('CREATE INDEX smtp_id ON pa_group (smtp_id)');
        $this->addSql('CREATE INDEX groupType ON pa_group (groupType)');
        $this->addSql('CREATE INDEX state ON pa_group (state)');
        $this->addSql('CREATE INDEX fk_bank_account_id ON pa_group (bank_account_id)');
        $this->addSql('CREATE INDEX objectId ON pa_group (sisId)');
        $this->addSql('ALTER TABLE pa_group_unit CHANGE group_id group_id INT UNSIGNED NOT NULL');
    }
}
