<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\Education;
use App\Model\Event\ReadModel\Queries\EducationQuery;
use App\Model\Event\Repositories\IEducationRepository;

class EducationQueryHandler
{
    public function __construct(private IEducationRepository $repository)
    {
    }

    public function __invoke(EducationQuery $query): Education
    {
        return $this->repository->find($query->getEducationId());
    }
}
