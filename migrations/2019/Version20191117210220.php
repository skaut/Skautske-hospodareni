<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191117210220 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_payment` CHANGE `transactionId` `transactionId` varchar(64) NULL AFTER `note`;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_payment` CHANGE `transactionId` `transactionId` int(10) unsigned NULL AFTER `note`;');
    }
}
