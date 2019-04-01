<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190401103007 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_commands` ADD `unit` varchar(64) NOT NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_commands` DROP `unit`;');
    }
}
