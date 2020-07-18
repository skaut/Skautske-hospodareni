<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200112173514 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Rozdělení vratek u tábora na dětské a dospělé';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `virtual`, `orderby`, `deleted`) VALUES
            (21, 'Vratka úč. poplatku - dítě',	'vrc',  'out', 1, 100, 0),
            (22, 'Vratka úč. poplatku - dospělý','vra', 'out', 1, 100, 0);
        SQL);
        $this->addSql('INSERT INTO `ac_chitsCategory_object` (`categoryId`, `objectTypeId`) VALUES (21,	\'camp\'), (22,	\'camp\');');

        $ids = $this->connection->fetchAll(<<<SQL
                SELECT i.id
                FROM `ac_chits_item` i
                left join ac_chit_to_item ci ON i.id = ci.item_id
                left join ac_chits c ON c.id = ci.chit_id
                LEFT JOIN ac_object o ON c.eventId = o.id
                WHERE `category` = '20' AND type ='camp'
        SQL);

        foreach ($ids as $row) {
            $this->connection->update(
                'ac_chits_item',
                ['category' => 21],
                ['id' => $row['id']]
            );
        }

        $this->addSql('DELETE FROM `ac_chitsCategory_object` WHERE `categoryId` = \'20\' AND `objectTypeId` = \'camp\';');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('INSERT INTO `ac_chitsCategory_object` (`categoryId`, `objectTypeId`) VALUES (20, \'camp\');');
        $ids = $this->connection->fetchAll(<<<SQL
                SELECT i.id
                FROM `ac_chits_item` i
                left join ac_chit_to_item ci ON i.id = ci.item_id
                left join ac_chits c ON c.id = ci.chit_id
                LEFT JOIN ac_object o ON c.eventId = o.id
                WHERE (`category` = '21' OR `category` = '22') AND type ='camp'
        SQL);

        foreach ($ids as $row) {
            $this->connection->update(
                'ac_chits_item',
                ['category' => 20],
                ['id' => $row['id']]
            );
        }

        $this->addSql('DELETE FROM `ac_chitsCategory_object` WHERE `categoryId` IN (\'21\', \'22\');');
        $this->addSql('DELETE FROM `ac_chitsCategory` WHERE `id` IN (\'21\', \'22\');');
    }
}
