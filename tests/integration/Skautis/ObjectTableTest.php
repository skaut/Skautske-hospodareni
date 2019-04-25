<?php

declare(strict_types=1);

namespace Model\Skautis;

use IntegrationTest;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;

final class ObjectTableTest extends IntegrationTest
{
    /** @var ObjectTable */
    private $objectTable;

    protected function _before() : void
    {
        parent::_before();
        $connection = $this->entityManager->getConnection();
        $connection->executeQuery('DROP TABLE IF EXISTS ac_object');
        $connection->executeQuery(<<<'SQL'
            CREATE TABLE `ac_object` (
              `id` varchar(36) NOT NULL,
              `skautisId` int(10) unsigned NOT NULL,
              `type` varchar(20) COLLATE utf8_czech_ci NOT NULL,
              `prefix` varchar(6) COLLATE utf8_czech_ci DEFAULT '',
              PRIMARY KEY (`id`),
              UNIQUE KEY `skautisId_type` (`skautisId`,`type`),
              KEY `type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
SQL
        );

        $this->objectTable = new ObjectTable($connection);
    }

    public function testAdd() : void
    {
        $skautisId  = 15;
        $cashbookId = CashbookId::generate();
        $type       = ObjectType::EVENT;

        $this->objectTable->add($skautisId, $cashbookId, $type);

        $this->tester->seeInDatabase('ac_object', [
            'id' => $cashbookId->toString(),
            'skautisId' => $skautisId,
            'type' => $type,
        ]);
    }

    public function testGetLocalId() : void
    {
        $skautisId  = 25;
        $cashbookId = CashbookId::generate();
        $type       = ObjectType::CAMP;

        $this->tester->haveInDatabase('ac_object', [
            'id' => $cashbookId->toString(),
            'skautisId' => $skautisId,
            'type' => $type,
        ]);

        $localId = $this->objectTable->getLocalId($skautisId, $type);

        $this->assertTrue($localId->equals($cashbookId));
    }

    public function testGetLocalIdReturnsNullIfObjectDoesNotExist() : void
    {
        $this->assertNull($this->objectTable->getLocalId(3, ObjectType::CAMP));
    }

    public function testGetSkautisId() : void
    {
        $skautisId  = 25;
        $cashbookId = CashbookId::generate();
        $type       = ObjectType::CAMP;

        $this->tester->haveInDatabase('ac_object', [
            'id' => $cashbookId->toString(),
            'skautisId' => $skautisId,
            'type' => $type,
        ]);

        $returnedSkautisId = $this->objectTable->getSkautisId($cashbookId, $type);

        $this->assertSame($skautisId, $returnedSkautisId);
    }

    public function testGetSkautisIdReturnsNullIfObjectDoesNotExist() : void
    {
        $this->assertNull($this->objectTable->getSkautisId(CashbookId::generate(), ObjectType::CAMP));
    }

    protected function _after() : void
    {
        $this->entityManager->getConnection()->executeQuery('DROP TABLE IF EXISTS ac_object');
    }
}
