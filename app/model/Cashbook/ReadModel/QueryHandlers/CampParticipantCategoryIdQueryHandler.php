<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CampParticipantCategoryIdQuery;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Nette\InvalidStateException;

class CampParticipantCategoryIdQueryHandler
{

    /** @var ICampCategoryRepository */
    private $categories;

    public function __construct(ICampCategoryRepository $categories)
    {
        $this->categories = $categories;
    }

    public function handle(CampParticipantCategoryIdQuery $query): int
    {
        $participantType = $query->getParticipantType();

        foreach ($this->categories->findForCamp($query->getCampId()->toInt()) as $category) {
            if ($category->getParticipantType() !== NULL && $category->getParticipantType()->equals($participantType)) {
                return $category->getId();
            }
        }

        throw new InvalidStateException(
            sprintf('There is no participant category for participant type %s.', $participantType->getValue())
        );
    }

}
