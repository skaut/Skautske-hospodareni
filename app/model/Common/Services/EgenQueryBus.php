<?php

declare(strict_types=1);

namespace Model\Common\Services;

use eGen\MessageBus\QueryBus\IQueryBus;

final class EgenQueryBus implements QueryBus
{
    private IQueryBus $queryBus;

    public function __construct(IQueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @return mixed
     */
    public function handle(object $query)
    {
        return $this->queryBus->handle($query);
    }
}
