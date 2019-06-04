<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190606072844 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_participants`
            ADD PRIMARY KEY `id` (`id`),
            DROP INDEX `PRIMARY`;
        SQL);

        $this->addSql('ALTER TABLE `ac_participants` ADD UNIQUE `participantId_event_type` (`participantId`, `event_type`);');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_participants`
            ADD PRIMARY KEY `participantId` (`participantId`),
            DROP INDEX `PRIMARY`;
        SQL);
        $this->addSql('ALTER TABLE `ac_participants` DROP INDEX `participantId_event_type`;');
    }
}
