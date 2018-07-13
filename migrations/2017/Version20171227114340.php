<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171227114340 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE `pa_smtp` ADD `sender` varchar(255) COLLATE 'utf8_czech_ci' NOT NULL AFTER `secure`;");
        $this->addSql('UPDATE `pa_smtp` SET sender = username');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `pa_smtp` DROP `sender`;');
    }
}
