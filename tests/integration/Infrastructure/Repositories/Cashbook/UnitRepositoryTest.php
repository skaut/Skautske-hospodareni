<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Hskauting\Tests\NullEventBus;
use IntegrationTest;
use Mockery;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Events\Unit\CashbookWasCreated;
use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\Unit;
use Model\Common\Services\EventBus;
use Model\Common\UnitId;

final class UnitRepositoryTest extends IntegrationTest
{
    private const UNIT = [
        'id' => 15,
        'active_cashbook_id' => 1,
        'next_cashbook_id' => 2,
    ];

    private const CASHBOOK = [
        'id' => 1,
        'unit_id' => 15,
        'year' => 2018,
        'cashbook_id' => '1e71a1e0-6202-4023-8649-a6068669bb1f',
    ];

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Unit::class];
    }

    public function testSaveAddsRowsToDatabase(): void
    {
        $unit = new Unit(
            new UnitId(self::UNIT['id']),
            CashbookId::fromString(self::CASHBOOK['cashbook_id']),
            self::CASHBOOK['year'],
        );

        $this->getRepository()->save($unit);

        $this->tester->seeInDatabase('ac_units', self::UNIT);

        $this->tester->seeInDatabase('ac_unit_cashbooks', self::CASHBOOK);
    }

    public function testFindMethodThrowsExceptionIfUnitDoesNotExist(): void
    {
        $this->expectException(UnitNotFound::class);

        $this->getRepository()->find(new UnitId(1));
    }

    public function testFindReturnsCorrectlyHydratedAggregate(): void
    {
        $this->tester->haveInDatabase('ac_units', self::UNIT);
        $this->tester->haveInDatabase('ac_unit_cashbooks', self::CASHBOOK);

        $unit = $this->getRepository()->find(new UnitId(self::UNIT['id']));

        $this->assertSame(self::UNIT['id'], $unit->getId()->toInt());
        $this->assertSame(self::UNIT['active_cashbook_id'], $unit->getActiveCashbook()->getId());
        $this->assertCount(1, $unit->getCashbooks());
        $this->assertSame(self::CASHBOOK['year'], $unit->getCashbooks()[0]->getYear());
        $this->assertSame(self::CASHBOOK['cashbook_id'], $unit->getCashbooks()[0]->getCashbookId()->toString());
    }

    public function testFindByCashbookIdReturnsCorrectUnit(): void
    {
        $this->tester->haveInDatabase('ac_units', self::UNIT);
        $this->tester->haveInDatabase('ac_unit_cashbooks', self::CASHBOOK);

        $unit = $this->getRepository()->findByCashbookId(CashbookId::fromString(self::CASHBOOK['cashbook_id']));

        $this->assertSame(self::UNIT['id'], $unit->getId()->toInt());
    }

    public function testFindByCashbookIdThrowsExceptionIfUnitIsNotFound(): void
    {
        $this->expectException(UnitNotFound::class);

        $this->getRepository()->findByCashbookId(CashbookId::generate());
    }

    public function testSaveDispatchesEvent(): void
    {
        $unit = new Unit(new UnitId(15), CashbookId::generate(), 2018);  // There is CashbookWasCreatedEvent for initial cashbook
        $unit->createCashbook(2019); // There is CashbookWasCreatedEvent

        $eventBus = Mockery::mock(EventBus::class);
        $eventBus->shouldReceive('handle')
            ->twice()
            ->withArgs(static function (CashbookWasCreated $event) {
                return true;
            });

        $this->getRepository($eventBus)->save($unit);
    }

    private function getRepository(EventBus|null $eventBus = null): UnitRepository
    {
        return new UnitRepository($this->entityManager, $eventBus ?? new NullEventBus());
    }
}
