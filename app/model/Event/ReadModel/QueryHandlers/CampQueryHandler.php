<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\Repositories\ICampRepository;

class CampQueryHandler
{
    private ICampRepository $campRepository;

    public function __construct(ICampRepository $campRepository)
    {
        $this->campRepository = $campRepository;
    }

    public function __invoke(CampQuery $query) : Camp
    {
        return $this->campRepository->find($query->getCampId());
    }
}
