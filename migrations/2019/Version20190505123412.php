<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190505123412 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_chits_item` ADD `purpose` varchar(120) NOT NULL AFTER `chit_id`;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_chits_item` DROP `purpose`;');
    }
}
