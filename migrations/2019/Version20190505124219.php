<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190505124219 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $chits = $this->connection->fetchAll('SELECT id, purpose FROM `ac_chits`');
        foreach ($chits as $chit) {
            $this->addSql('UPDATE `ac_chits_item` SET `purpose` = :purpose WHERE `chit_id` = :id;', $chit);
        }
        $this->addSql('ALTER TABLE `ac_chits` CHANGE `purpose` `x_purpose` varchar(120) NULL AFTER `recipient`;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_chits` CHANGE `x_purpose` `purpose` varchar(120) NOT NULL AFTER `recipient`;');
    }
}
