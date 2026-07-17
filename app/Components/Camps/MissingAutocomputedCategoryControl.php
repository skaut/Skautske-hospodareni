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
use LogicException;

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
        $camp = $this->queryBus->handle(new CampQuery($this->campId));
        if (! $camp instanceof \App\Model\Event\Camp) {
            throw new LogicException('Assertion failed.');
        }
        if (! $this->isApproved($camp->getState())) {
            $this->reload('Dopočítávání rozpočtových kategorií lze aktivovat až po schválení tábora střediskovou radou.', 'danger');
        }

        if (! $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->campId->toInt())) {
            $this->reload('Nemáte oprávnění aktivovat automatické dopočítávání rozpočtu tábora.', 'danger');
        }

        if (! $this->authorizator->isAllowed(Camp::UPDATE_BUDGET, $this->campId->toInt())) {
            $this->reload('Nemáte oprávnění upravovat rozpočtové kategorie tábora ve skautISu.', 'danger');
        }

        $this->commandBus->handle(new ActivateAutocomputedCashbook($this->campId));
        $this->reload('Bylo aktivováno automatické dopočítávání rozpočtových kategorií.');
    }

    public function render(): void
    {
        $camp = $this->queryBus->handle(new CampQuery($this->campId));
        if (! $camp instanceof \App\Model\Event\Camp) {
            throw new LogicException('Assertion failed.');
        }
        $this->template->setFile(__DIR__.'/templates/MissingAutocomputedCategoryControl.latte');
        $isApproved = $this->isApproved($camp->getState());
        $canUpdateBudget = $this->authorizator->isAllowed(Camp::UPDATE_BUDGET, $this->campId->toInt());
        $canActivateAutocomputedBudget = $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->campId->toInt());
        $this->template->setParameters([
            'isApproved' => $isApproved,
            'canUpdateBudget' => $canUpdateBudget,
            'canActivateAutocomputedBudget' => $canActivateAutocomputedBudget,
            'canActivate' => $isApproved && $canUpdateBudget && $canActivateAutocomputedBudget,
        ]);
        $this->template->render();
    }

    private function isApproved(string $campState): bool
    {
        return in_array($campState, [CampState::APPROVED_PARENT->value, CampState::REAL->value], true);
    }
}
