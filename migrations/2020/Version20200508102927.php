<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200508102927 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_cashbook` ADD `bank_chit_number_prefix` varchar(255) COLLATE \'utf8_czech_ci\' NULL AFTER `chit_number_prefix`;');
        $this->addSql('ALTER TABLE `ac_cashbook` CHANGE `chit_number_prefix` `cash_chit_number_prefix` varchar(255) COLLATE \'utf8_czech_ci\' NULL AFTER `type`;');
        $this->addSql('UPDATE `ac_cashbook` SET `bank_chit_number_prefix`=`cash_chit_number_prefix` ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_cashbook` CHANGE `cash_chit_number_prefix` `chit_number_prefix` varchar(255) COLLATE \'utf8_czech_ci\' NULL AFTER `type`;');
        $this->addSql('ALTER TABLE `ac_cashbook` DROP `bank_chit_number_prefix`;');
    }
}
