<?php

declare(strict_types=1);

namespace App;

use App\Components\Factories\Payment\ICreateButtonFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
use App\Components\Payment\CreateButton;
use App\Components\Payment\PairButton;
use App\Model\Event\ReadModel\Queries\CampStatsQuery;
use App\Model\Event\ReadModel\Queries\EventStatsQuery;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use Nette\Utils\DateTime;
use Skautis\Wsdl\AuthenticationException;

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
        if (! $this->getUser()->isLoggedIn()) {
            $this->redirect(':Default:default');
        }

        try {
            $this->template->campsCount = $this->queryBus->handle(new CampStatsQuery((int) (new DateTime())->format('Y')));
            $groups = $this->queryBus->handle(
                new GetGroupList(array_keys($this->unitService->getReadUnits($this->user)), true),
            );

            $this->template->groupCount = count($groups);
            $this->template->eventsCount = $this->queryBus->handle(new EventStatsQuery((int) (new DateTime())->format('Y')));

            $groupIds = [];
            foreach ($groups as $group) {
                $groupIds[] = $group->getId();
            }

            $this['pairButton']->setGroups($groupIds);
        } catch (AuthenticationException) {
            $this->getUser()->logout(true);
            $this->flashMessage('Uživatel byl odhlášen', 'danger');
            $this->redirect(':Default:');
        }
    }

    protected function createComponentPairButton(): PairButton
    {
        $control = $this->pairButtonFactory->create();
        $control->addCss([
            'wrap' => 'd-inline-block',
            'btn' => 'btn btn-sm btn-light',
            'toggle' => 'btn btn-sm btn-light dropdown-toggle',
            'menu' => 'dropdown-menu pairForm dropdown-menu-end',
            'icon' => 'fa-solid fa-building-columns',
            'inputGroup' => 'input-group input-group-sm',
            'submit' => 'btn btn-primary btn-sm',
            'submitCol' => 'col-4 d-grid',
        ]);

        return $control;
    }

    protected function createComponentCreateButton(): CreateButton
    {
        $control = $this->createButtonFactory->create();
        $control->addCss([
            'wrap' => 'd-inline-block',
            'group' => 'btn-group',
            'main' => 'btn btn-sm btn-success',
            'toggle' => 'btn btn-sm btn-success dropdown-toggle dropdown-toggle-split',
            'menu' => 'dropdown-menu dropdown-menu-end',
        ]);

        return $control;
    }
}
