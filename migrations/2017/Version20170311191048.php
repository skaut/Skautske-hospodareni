<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170311191048 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_group` ADD `nextVs` int(4) unsigned NULL AFTER `ks`;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_group` DROP `nextVs`;');
    }
}
