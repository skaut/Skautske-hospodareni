<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\PaymentModule\Components\CreateButton;
use App\AccountancyModule\PaymentModule\Components\PairButton;
use App\AccountancyModule\PaymentModule\Factories\ICreateButtonFactory;
use App\AccountancyModule\PaymentModule\Factories\IPairButtonFactory;
use Model\Event\ReadModel\Queries\CampStatsQuery;
use Model\Event\ReadModel\Queries\EventStatsQuery;
use Model\Payment\ReadModel\Queries\GetGroupList;
use Nette\Utils\DateTime;

use function array_keys;
use function count;

class DashboardPresenter extends BasePresenter
{
    public function __construct(private readonly IPairButtonFactory $pairButtonFactory, private readonly ICreateButtonFactory $createButtonFactory)
    {
        parent::__construct();
    }

    public function actionDefault(): void
    {
        $this->template->campsCount = $this->queryBus->handle(new CampStatsQuery((int) (new DateTime())->format('Y')));
        $groups                     = $this->queryBus->handle(
            new GetGroupList(array_keys($this->unitService->getReadUnits($this->user)), true),
        );

        $this->template->groupCount  = count($groups);
        $this->template->eventsCount = $this->queryBus->handle(new EventStatsQuery((int) (new DateTime())->format('Y')));

        $groupIds = [];
        foreach ($groups as $group) {
            $groupIds[] = $group->getId();
        }

        $this['pairButton']->setGroups($groupIds);
    }

    protected function createComponentPairButton(): PairButton
    {
        $control = $this->pairButtonFactory->create();
        $control->addCss([
            'wrap'       => 'section-actions d-inline-flex align-items-center gap-2',
            'btn'        => 'btn btn-outline-secondary btn-sm btn-surface',
            'toggle'     => 'btn btn-outline-secondary btn-sm dropdown-toggle btn-surface',
            'menu'       => 'dropdown-menu pairForm dropdown-menu-end',
            'icon'       => 'fa-solid fa-building-columns',
            'inputGroup' => 'input-group input-group-sm',
            'submit'     => 'btn btn-primary btn-sm',
            'submitCol'  => 'col-4 d-grid',
        ]);

        return $control;
    }

    protected function createComponentCreateButton(): CreateButton
    {
        $control = $this->createButtonFactory->create();
        $control->setCss('button', 'btn btn-sm btn-outline-secondary');

        return $control;
    }
}
