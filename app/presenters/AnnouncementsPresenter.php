<?php

declare(strict_types=1);

namespace App;

use App\Model\Announcement\Repository\AnnouncementRepository;

final class AnnouncementsPresenter extends BasePresenter
{
    public function __construct(private readonly AnnouncementRepository $announcementRepository)
    {
        parent::__construct();
    }

    public function actionDefault(): void
    {
        if (! $this->getUser()->isLoggedIn()) {
            $this->redirect(':Default:default');
        }

        $this->template->announcements = $this->announcementRepository->findVisible(new \DateTimeImmutable());
    }
}
