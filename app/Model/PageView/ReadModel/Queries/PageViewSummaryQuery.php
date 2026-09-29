<?php

declare(strict_types=1);

namespace App\Model\PageView\ReadModel\Queries;

use App\Model\PageView\ReadModel\QueryHandlers\PageViewSummaryQueryHandler;
use DateTimeImmutable;

/** @see PageViewSummaryQueryHandler */
final class PageViewSummaryQuery
{
    public function __construct(private DateTimeImmutable $from, private DateTimeImmutable $to)
    {
    }

    public function getFrom(): DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable
    {
        return $this->to;
    }
}
