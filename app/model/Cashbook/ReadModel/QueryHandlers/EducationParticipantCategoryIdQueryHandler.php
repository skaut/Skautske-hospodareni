<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationParticipantCategoryIdQuery;
use Model\Cashbook\Repositories\IEducationCategoryRepository;
use Nette\InvalidStateException;

class EducationParticipantCategoryIdQueryHandler
{
    public function __construct(private IEducationCategoryRepository $categories)
    {
    }

    public function __invoke(EducationParticipantCategoryIdQuery $query): int
    {
        foreach ($this->categories->findForEducation($query->getEducationId()->toInt()) as $category) {
            if ($category->getName() === 'Účastnické poplatky') {
                return $category->getId();
            }
        }

        throw new InvalidStateException('There is no participant category.');
    }
}
