<?php

declare(strict_types=1);

namespace App\Model\Usage\ReadModel\QueryHandlers;

use App\Model\DTO\Usage\PageSessions;
use App\Model\Usage\ReadModel\Queries\PageSessionsQuery;
use App\Model\Usage\Repository\UsageRepository;

final class PageSessionsQueryHandler
{
    public function __construct(private UsageRepository $repository)
    {
    }

    public function __invoke(PageSessionsQuery $query): PageSessions
    {
        return new PageSessions(
            $this->repository->sumByPage($query->getFrom(), $query->getTo()),
            $this->repository->findFirstDay(),
        );
    }
}
