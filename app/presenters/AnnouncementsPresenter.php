<?php

declare(strict_types=1);

namespace App;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Model\Announcement\Entity\Announcement;
use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use App\Model\Announcement\Repository\AnnouncementRepository;
use DateTimeImmutable;

final class AnnouncementsPresenter extends BasePresenter
{
    /** @var Announcement[] */
    private array $visibleAnnouncements = [];

    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
        private readonly GridFactory $gridFactory,
    ) {
        parent::__construct();
    }

    public function actionDefault(): void
    {
        $this->requireLogin();
        $this->visibleAnnouncements = $this->announcementRepository->findVisible(new DateTimeImmutable());
        $this->template->announcements = $this->visibleAnnouncements;
    }

    private function requireLogin(): void
    {
        if (! $this->getUser()->isLoggedIn()) {
            $this->redirect(':Default:default');
        }
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->create(__DIR__.'/../templates/Announcements/grid.latte', ['searchLabel' => 'Hledat oznámení']);
        $grid->setPrimaryKey('id');
        $grid->setOuterFilterRendering(true);
        $grid->setCollapsibleOuterFilters(false);
        $grid->setColumnReset(false);
        $grid->setRememberState(false);
        $grid->setDefaultSort(['publishedAt' => DataGrid::SORT_DESC]);

        $rows = [];
        foreach ($this->visibleAnnouncements as $announcement) {
            $rows[] = [
                'id' => $announcement->getId(),
                'title' => $announcement->getTitle(),
                'message' => $announcement->getMessage(),
                'categoryCode' => $announcement->getCategory()->getCode()->value,
                'category' => $announcement->getCategory()->getCode()->label(),
                'categoryIcon' => $announcement->getCategory()->getCode()->icon(),
                'publishedAt' => $announcement->getPublishedAt(),
            ];
        }
        $grid->setDataSource($rows);

        $grid->addColumnText('category', 'Kategorie')->setSortable();
        $grid->addColumnText('title', 'Oznámení')->setSortable();
        $grid->addColumnDateTime('publishedAt', 'Publikováno')
            ->setFormat('j. n. Y H:i')
            ->setSortable();
        $grid->addFilterText('search', '', ['title', 'message'])
            ->setPlaceholder('Hledat oznámení...');
        $grid->addFilterSelect('categoryCode', 'Kategorie', AnnouncementCategoryCode::options())
            ->setPrompt('Všechny');

        return $grid;
    }
}
