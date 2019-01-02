<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190102200136 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_chitsCategory` ' .
            "CHANGE `type` `type` enum('in','out') COLLATE 'utf8_czech_ci' NOT NULL AFTER `short`, " .
            'CHANGE `deleted` `deleted` tinyint(4) NOT NULL AFTER `orderby`;');

        $this->addSql('ALTER TABLE `pa_group` ' .
            "CHANGE `state` `state` varchar(20) COLLATE 'utf8_czech_ci' NOT NULL AFTER `nextVs`, " .
            "CHANGE `state_info` `state_info` varchar(250) COLLATE 'utf8_czech_ci' NOT NULL AFTER `state`;");

        $this->addSql("ALTER TABLE `pa_smtp` CHANGE `secure` `secure` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL AFTER `password`;");

        $this->addSql('ALTER TABLE `pa_payment` ' .
            "CHANGE `note` `note` varchar(64) COLLATE 'utf8_czech_ci' NOT NULL AFTER `ks`, " .
            "CHANGE `state` `state` varchar(20) COLLATE 'utf8_czech_ci' NOT NULL AFTER `paidFrom`;");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
