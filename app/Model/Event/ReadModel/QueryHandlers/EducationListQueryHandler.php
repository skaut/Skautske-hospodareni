<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\Education;
use App\Model\Event\ReadModel\Queries\EducationListQuery;
use App\Model\Skautis\Factory\EducationFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

class EducationListQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationFactory $educationFactory)
    {
    }

    /** @return array<int, Education> Educations indexed by ID */
    public function __invoke(EducationListQuery $query): array
    {
        $educations = $this->skautis->event->EventEducationAllMyActions([
            'Year' => $query->getYear(),
        ]);

        if (is_object($educations)) {
            return [];
        }

        $result = [];
        foreach ($educations as $education) {
            $education = $this->educationFactory->create($education);
            assert($education instanceof Education);

            $result[$education->getId()->toInt()] = $education;
        }

        return $result;
    }
}
