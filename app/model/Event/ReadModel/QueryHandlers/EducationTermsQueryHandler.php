<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationTerm;
use Model\Event\ReadModel\Queries\EducationTermsQuery;
use Model\Skautis\Factory\EducationTermFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

class EducationTermsQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationTermFactory $educationTermFactory)
    {
    }

    /** @return array<int, EducationTerm> Education terms indexed by ID */
    public function __invoke(EducationTermsQuery $query): array
    {
        $terms = $this->skautis->event->EventEducationCourseTermAll([
            'ID_EventEducation' => $query->getEventEducationId(),
        ]);

        if (is_object($terms)) {
            return [];
        }

        $result = [];
        foreach ($terms as $term) {
            $term = $this->educationTermFactory->create($term);
            assert($term instanceof EducationTerm);

            $result[$term->getId()->toInt()] = $term;
        }

        return $result;
    }
}
