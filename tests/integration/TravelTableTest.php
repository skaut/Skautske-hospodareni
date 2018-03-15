<?php

declare(strict_types=1);

namespace Model;

use Doctrine\DBAL\Connection;

final class TravelTableTest extends \IntegrationTest
{

    /** @var Connection */
    private $connection;

    /** @var TravelTable */
    private $table;

    protected function _before()
    {
        $this->tester->useConfigFiles([
            __DIR__ . '/TravelTableTest.neon',
        ]);

        parent::_before();

        $this->connection = $this->tester->grabService(Connection::class);
        $this->table = new TravelTable($this->tester->grabService(\Dibi\Connection::class));

        $this->cleanup();

        $sql = 'CREATE TABLE `tc_travelTypes` (
              `type` varchar(5) COLLATE utf8_czech_ci NOT NULL,
              `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
              `hasFuel` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
              `order` tinyint(4) NOT NULL DEFAULT \'10\',
              PRIMARY KEY (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
            
            INSERT INTO `tc_travelTypes` (`type`, `label`, `hasFuel`, `order`) VALUES
            (\'a\',	\'autobus\',	0,	45),
            (\'auv\',	\'auto vlastní\',	1,	50),
            (\'l\',	\'letadlo\',	0,	9),
            (\'mov\',	\'motocykl vlastní\',	1,	30),
            (\'o\',	\'osobní vlak\',	0,	40),
            (\'p\',	\'pěšky\',	0,	10),
            (\'r\',	\'rychlík\',	0,	40);
        ';

        $this->connection->exec($sql);
    }

    public function testPairs(): void
    {
        $expected = [
            'auv'   => 'auto vlastní',
            'a'     => 'autobus',
            'o'     => 'osobní vlak',
            'r'     => 'rychlík',
            'mov'   => 'motocykl vlastní',
            'p'     => 'pěšky',
            'l'     => 'letadlo',
        ];

        $this->assertSame($expected, $this->table->getTypes(TRUE));
    }

    protected function _after()
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        $this->connection->exec('DROP TABLE IF EXISTS tc_travelTypes');
    }

}
