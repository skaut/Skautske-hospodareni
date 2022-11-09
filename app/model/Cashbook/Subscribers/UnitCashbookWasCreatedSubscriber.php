<?php

declare(strict_types=1);

namespace Model\Cashbook\Subscribers;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Events\Unit\CashbookWasCreated;
use Model\Common\Services\CommandBus;
use Model\Payment\IUnitResolver;

final class UnitCashbookWasCreatedSubscriber
{
    private CommandBus $commandBus;

    private IUnitResolver $unitResolver;

    public function __construct(CommandBus $commandBus, IUnitResolver $unitResolver)
    {
        $this->commandBus   = $commandBus;
        $this->unitResolver = $unitResolver;
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
