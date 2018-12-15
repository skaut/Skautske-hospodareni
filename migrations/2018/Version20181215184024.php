<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181215184024 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_commands` ADD `ownerId` int(11) NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `tc_commands` DROP `ownerId`;');
    }
}
