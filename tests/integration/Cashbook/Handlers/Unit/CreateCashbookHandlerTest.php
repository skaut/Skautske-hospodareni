<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Unit;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Commands\Unit\CreateCashbook;
use App\Model\Cashbook\Unit;
use App\Model\Common\UnitId;
use App\Model\Payment\UnitResolverStub;
use CommandHandlerTest;

use function assert;

final class CreateCashbookHandlerTest extends CommandHandlerTest
{
    private const YEAR = 2019;
    private const OFFICIAL_UNITS = [
        1 => 1,
        2 => 1,
    ];

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__.'/CreateCashbookHandlerTest.neon']);

        parent::_before();

        $this->tester->grabService(UnitResolverStub::class)->setOfficialUnits(self::OFFICIAL_UNITS);
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Unit::class,
            Cashbook::class,
        ];
    }

    /** @dataProvider dataCashbookTypes */
    public function testUnitCashbookAndCashbookAggregateAreCreated(CashbookType $cashbookType, UnitId $unitId): void
    {
        $this->entityManager->persist(new Unit($unitId, Cashbook\CashbookId::generate(), 2018));
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->commandBus->handle(new CreateCashbook($unitId, self::YEAR));

        $unit = $this->entityManager->find(Unit::class, $unitId);
        assert($unit instanceof Unit);
        $cashbookId = $unit->getCashbooks()[1]->getCashbookId();

        $cashbook = $this->entityManager->find(Cashbook::class, $cashbookId);
        assert($cashbook instanceof Cashbook);
        $this->assertTrue($cashbook->getType()->equals($cashbookType), 'Correct cashbook type is assigned');
    }

    /** @return mixed[][] */
    public function dataCashbookTypes(): array
    {
        return [
            [CashbookType::get(CashbookType::OFFICIAL_UNIT), new UnitId(1)],
            [CashbookType::get(CashbookType::TROOP), new UnitId(2)],
        ];
    }
}
