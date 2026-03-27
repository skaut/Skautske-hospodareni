<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Subscribers;

use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Commands\Cashbook\CreateCashbook;
use App\Model\Cashbook\Events\Unit\CashbookWasCreated;
use App\Model\Common\Services\CommandBus;
use App\Model\Payment\IUnitResolver;

final class UnitCashbookWasCreatedSubscriber
{
    public function __construct(private CommandBus $commandBus, private IUnitResolver $unitResolver)
    {
    }

    public function __invoke(CashbookWasCreated $event): void
    {
        $unitId = $event->getUnitId()->toInt();

        $cashbookType = CashbookType::get(
            $this->unitResolver->getOfficialUnitId($unitId) === $unitId
                ? CashbookType::OFFICIAL_UNIT
                : CashbookType::TROOP,
        );

        $this->commandBus->handle(new CreateCashbook($event->getCashbookId(), $cashbookType));
    }
}
