<?php

declare(strict_types=1);

namespace App\Model\PageView\ReadModel\QueryHandlers;

use App\Model\DTO\PageView\PageViewSummary;
use App\Model\PageView\ReadModel\Queries\PageViewSummaryQuery;
use App\Model\PageView\Repository\PageViewDailyRepository;

final class PageViewSummaryQueryHandler
{
    public function __construct(private PageViewDailyRepository $repository)
    {
    }

    public function __invoke(PageViewSummaryQuery $query): PageViewSummary
    {
        return new PageViewSummary(
            $this->repository->sumByPage($query->getFrom(), $query->getTo()),
            $this->repository->findFirstDay(),
        );
    }
}
