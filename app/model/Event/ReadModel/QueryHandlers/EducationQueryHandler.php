<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Education;
use Model\Event\ReadModel\Queries\EducationQuery;
use Model\Event\Repositories\IEducationRepository;

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
