<?php

declare(strict_types=1);

namespace App\Model\Usage\ReadModel\Queries;

use App\Model\Usage\ReadModel\QueryHandlers\PageSessionsQueryHandler;
use DateTimeImmutable;

/**
 * Sessions per page (`Presenter:action`) over a closed range of days, for the page list of Admin:Help.
 *
 * @see PageSessionsQueryHandler
 */
final class PageSessionsQuery
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
