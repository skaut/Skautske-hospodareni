<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Logger;

use Doctrine\ORM\EntityManager;
use Model\Logger\Log\Type;
use Model\Logger\LogEntry;
use function array_merge;

final class LogEntryRepositoryTest extends \IntegrationTest
{
    private const TABLE = 'log';

    /** @var LogEntryRepository */
    private $repository;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            LogEntry::class,
        ];
    }

    protected function _before() : void
    {
        parent::_before();
        $this->repository = new LogEntryRepository($this->tester->grabService(EntityManager::class));
    }

    /**
     * @dataProvider dataTypeIds
     */
    public function testSave(?int $typeId) : void
    {
        $row = [
            'unitId' => 15,
            'userId' => 10,
            'description' => 'Something happened!',
            'type' => Type::PAYMENT,
            'typeId' => $typeId,
            'date' => '2018-01-01 00:10:15',
        ];

        $this->repository->save(
            new LogEntry(
                $row['unitId'],
                $row['userId'],
                $row['description'],
                Type::get($row['type']),
                $row['typeId'],
                new \DateTimeImmutable($row['date'])
            )
        );

        $this->tester->seeInDatabase(self::TABLE, $row);
    }

    public function testFindAllByTypeId() : void
    {
        $rows = [
            [
                'type' => Type::PAYMENT,
                'typeId' => null,
            ],
            [
                'type' => Type::OBJECT,
                'typeId' => 2,
            ],
            [
                'type' => Type::OBJECT,
                'typeId' => 3,
            ],
            [
                'type' => Type::OBJECT,
                'typeId' => 3,
            ],
        ];

        $rowTemplate = [
            'unitId' => 15,
            'userId' => 10,
            'description' => 'Something happened!',
            'date' => '2018-01-01 00:10:15',
        ];

        foreach ($rows as $row) {
            $this->tester->haveInDatabase(self::TABLE, array_merge($row, $rowTemplate));
        }

        $entries = $this->repository->findAllByTypeId(Type::get(Type::OBJECT), 3);

        $this->assertCount(2, $entries);
        $this->assertSame(3, $entries[0]->getId());
        $this->assertSame(4, $entries[1]->getId());

        foreach ($entries as $entry) {
            $this->assertSame($rowTemplate['unitId'], $entry->getUnitId());
            $this->assertSame($rowTemplate['userId'], $entry->getUserId());
            $this->assertSame($rowTemplate['description'], $entry->getDescription());
            $this->assertEquals($rowTemplate['date'], $entry->getDate()->format('Y-m-d H:i:s'));
        }
    }

    /**
     * @return int[][]
     */
    public function dataTypeIds() : array
    {
        return [
            [15],
            [null],
        ];
    }

}
