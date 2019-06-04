<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Model\Participant\PaymentId;

final class Version20190604213432 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_participants`
            ADD `id` varchar(36) NOT NULL FIRST,
            CHANGE `participantId` `participantId` int(10) unsigned NOT NULL COMMENT 'ID' AFTER `id`;    
        SQL);
    }

    public function postUp(Schema $schema) : void
    {
        parent::postUp($schema);

        $data = $this->connection->fetchAll('SELECT * FROM `ac_participants`');
        foreach ($data as $row) {
            $this->connection->update(
                'ac_participants',
                ['id' => PaymentId::generate()->toString()],
                ['participantId' => $row['participantId'], 'event_type' => $row['event_type'], 'event_id' => $row['event_id']]
            );
        }
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `ac_participants`
            DROP `id`,
            CHANGE `participantId` `participantId` int(10) unsigned NOT NULL COMMENT 'ID' AUTO_INCREMENT FIRST;
        SQL);
    }
}
