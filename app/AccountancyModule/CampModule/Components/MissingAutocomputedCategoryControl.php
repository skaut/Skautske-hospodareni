<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\Components\BaseControl;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\SkautisCampId;
use function in_array;

class MissingAutocomputedCategoryControl extends BaseControl
{
    private SkautisCampId $campId;

    private IAuthorizator $authorizator;
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    public function __construct(
        SkautisCampId $campId,
        IAuthorizator $authorizator,
        QueryBus $queryBus,
        CommandBus $commandBus
    ) {
        parent::__construct();
        $this->campId       = $campId;
        $this->authorizator = $authorizator;
        $this->queryBus     = $queryBus;
        $this->commandBus   = $commandBus;
    }

    public function handleActivate() : void
    {
        $this->commandBus->handle(new ActivateAutocomputedCashbook($this->campId));
        $this->reload();
    }

    public function render() : void
    {
        /** @var \Model\Event\Camp $camp */
        $camp = $this->queryBus->handle(new CampQuery($this->campId));

        $this->template->setFile(__DIR__ . '/templates/MissingAutocomputedCategoryControl.latte');
        $this->template->setParameters([
            'isApproved' => in_array($camp->getState(), [\Model\Event\Camp::STATE_APPROVED_PARENT, \Model\Event\Camp::STATE_REAL]),
            'isEditable' => $this->authorizator->isAllowed(Camp::UPDATE_REAL, $this->campId->toInt()),
            'canActivate'   => $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->campId->toInt()),
        ]);
        $this->template->render();
    }
}
