<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\Components\BaseControl;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\SkautisCampId;

use function assert;
use function in_array;

class MissingAutocomputedCategoryControl extends BaseControl
{
    public function __construct(
        private SkautisCampId $campId,
        private IAuthorizator $authorizator,
        private QueryBus $queryBus,
        private CommandBus $commandBus,
    ) {
    }

    public function handleActivate(): void
    {
        $this->commandBus->handle(new ActivateAutocomputedCashbook($this->campId));
        $this->reload();
    }

    public function render(): void
    {
        $camp = $this->queryBus->handle(new CampQuery($this->campId));
        assert($camp instanceof \Model\Event\Camp);

        $this->template->setFile(__DIR__ . '/templates/MissingAutocomputedCategoryControl.latte');
        $this->template->setParameters([
            'isApproved' => in_array($camp->getState(), [\Model\Event\Camp::STATE_APPROVED_PARENT, \Model\Event\Camp::STATE_REAL]),
            'isEditable' => $this->authorizator->isAllowed(Camp::UPDATE_REAL, $this->campId->toInt()),
            'canActivate'   => $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->campId->toInt()),
        ]);
        $this->template->render();
    }
}
