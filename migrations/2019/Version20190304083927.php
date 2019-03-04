<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190304083927 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_camp_participants` ' .
                           'CHANGE `payment` `payment` float(9,2) unsigned NOT NULL AFTER `actionId`,' .
                           'CHANGE `repayment` `repayment` float(9,2) unsigned NOT NULL AFTER `payment`;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_camp_participants` ' .
                           'CHANGE `payment` `payment` float(9,2) unsigned NULL AFTER `actionId`,' .
                           'CHANGE `repayment` `repayment` float(9,2) unsigned NULL COMMENT \'vratka\' AFTER `payment`;');
    }
}
