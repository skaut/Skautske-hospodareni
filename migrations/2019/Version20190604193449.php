<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190604193449 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_camp_participants` RENAME TO `ac_participants`;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `ac_participants` RENAME TO `ac_camp_participants`;');
    }
}
