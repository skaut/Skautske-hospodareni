<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\Camp;
use App\Model\Event\ReadModel\Queries\CampQuery;
use App\Model\Event\Repositories\ICampRepository;

class CampQueryHandler
{
    public function __construct(private ICampRepository $campRepository)
    {
    }

    public function __invoke(CampQuery $query): Camp
    {
        return $this->campRepository->find($query->getCampId());
    }
}
