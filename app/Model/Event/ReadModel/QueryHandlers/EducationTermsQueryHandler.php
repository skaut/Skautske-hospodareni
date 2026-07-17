<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationTerm;
use App\Model\Event\ReadModel\Queries\EducationTermsQuery;
use App\Model\Skautis\Factory\EducationTermFactory;
use LogicException;
use Skautis\Skautis;

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
            if (! $term instanceof EducationTerm) {
                throw new LogicException('Assertion failed.');
            }
            $result[$term->getId()->toInt()] = $term;
        }

        return $result;
    }
}
