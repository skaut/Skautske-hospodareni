<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200406114332 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Map Money objects as integers (amount of cents) in database';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE tc_commands SET fuel_price = fuel_price * 100, amortization = amortization * 100');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_commands
                CHANGE fuel_price fuel_price INT NOT NULL COMMENT '(DC2Type:money)',
                CHANGE amortization amortization INT NOT NULL COMMENT '(DC2Type:money)'
        SQL);

        $this->addSql('UPDATE ac_participants SET payment = payment * 100, repayment = repayment * 100');
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_participants
                CHANGE payment payment INT NOT NULL COMMENT '(DC2Type:money)',
                CHANGE repayment repayment INT NOT NULL COMMENT '(DC2Type:money)'
        SQL);

        $this->addSql('UPDATE tc_travels SET price = price * 100');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travels CHANGE price price INT DEFAULT NULL COMMENT '(DC2Type:money)'
        SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ac_participants
                CHANGE payment payment NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)',
                CHANGE repayment repayment NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)'
        SQL);
        $this->addSql('UPDATE ac_participants SET payment = payment / 100, repayment = repayment / 100');

        $this->addSql(<<<'SQL'
            ALTER TABLE tc_commands
                CHANGE fuel_price fuel_price NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)',
                CHANGE amortization amortization NUMERIC(8, 2) NOT NULL COMMENT '(DC2Type:money)'
        SQL);
        $this->addSql('UPDATE tc_commands SET fuel_price = fuel_price / 100, amortization = amortization / 100');

        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travels CHANGE price price NUMERIC(8, 2) DEFAULT NULL COMMENT '(DC2Type:money)'
        SQL);
        $this->addSql('UPDATE tc_travels SET price = price / 100');
    }
}
