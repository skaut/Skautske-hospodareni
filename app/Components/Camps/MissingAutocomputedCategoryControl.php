<?php

declare(strict_types=1);

namespace App\Components\Camps;

use App\Components\BaseControl;
use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Camp;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use App\Model\Event\Enum\CampState;
use App\Model\Event\ReadModel\Queries\CampQuery;
use App\Model\Event\SkautisCampId;

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
        assert($camp instanceof \App\Model\Event\Camp);

        $this->template->setFile(__DIR__.'/templates/MissingAutocomputedCategoryControl.latte');
        $this->template->setParameters([
            'isApproved' => in_array($camp->getState(), [CampState::APPROVED_PARENT, CampState::REAL->value]),
            'isEditable' => $this->authorizator->isAllowed(Camp::UPDATE_REAL, $this->campId->toInt()),
            'canActivate' => $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->campId->toInt()),
        ]);
        $this->template->render();
    }
}
