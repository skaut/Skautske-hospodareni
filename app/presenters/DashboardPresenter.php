<?php

declare(strict_types=1);

namespace App;

use App\Components\Factories\Payment\ICreateButtonFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
use App\Components\Payment\CreateButton;
use App\Components\Payment\PairButton;
use App\Model\Announcement\Repository\AnnouncementRepository;
use App\Model\DTO\Payment\Group;
use App\Model\Event\ReadModel\Queries\CampStatsQuery;
use App\Model\Event\ReadModel\Queries\EventStatsQuery;
use App\Model\Google\OAuthId;
use App\Model\Google\ReadModel\Queries\BrokenSenderAccountsQuery;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use DateTimeImmutable;
use Nette\Utils\DateTime;
use Skaut\Skautis\Wsdl\AuthenticationException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function count;

class DashboardPresenter extends BasePresenter
{
    public function __construct(
        private readonly IPairButtonFactory $pairButtonFactory,
        private readonly ICreateButtonFactory $createButtonFactory,
        private readonly AnnouncementRepository $announcementRepository,
    ) {
        parent::__construct();
    }

    public function actionDefault(): void
    {
        if (! $this->getUser()->isLoggedIn()) {
            $this->redirect(':Default:default');
        }

        $visibleAnnouncements = $this->announcementRepository->findVisible(new DateTimeImmutable(), 4);
        $this->template->hasMoreAnnouncements = count($visibleAnnouncements) > 3;
        $this->template->dashboardAnnouncements = array_slice($visibleAnnouncements, 0, 3);
        $this->template->brokenSenders = [];

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

            // Only users of the groups that send from a refused account learn about it; it does not take a place of the announcements.
            $this->template->brokenSenders = $this->queryBus->handle(new BrokenSenderAccountsQuery(array_values(array_filter(
                array_map(static fn (Group $group): ?OAuthId => $group->getOAuthId(), $groups),
            ))));
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
            'icon' => 'fi fi-rr-bank',
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
            'main' => 'btn btn-sm btn-primary',
            'toggle' => 'btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split',
            'menu' => 'dropdown-menu dropdown-menu-end',
        ]);

        return $control;
    }
}
