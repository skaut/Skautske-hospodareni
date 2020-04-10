<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200410190351 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_payment` ADD `closed_by_username` varchar(64) NULL AFTER `dateClosed`;');
        $this->addSql('UPDATE `pa_payment` SET closed_by_username = "Hospodaření" WHERE transaction_payer IS NULL AND state = "completed"');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_payment` DROP `closed_by_username`;');
    }
}
