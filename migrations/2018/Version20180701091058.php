<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180701091058 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE `ac_cashbook` ADD `note` text COLLATE 'utf8_unicode_ci' NOT NULL;");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_cashbook` DROP `note`;');
    }
}
