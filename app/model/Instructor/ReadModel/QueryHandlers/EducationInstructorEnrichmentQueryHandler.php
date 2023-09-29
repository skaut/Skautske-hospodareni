<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationInstructorEnrichmentQuery;
use Model\DTO\Instructor\InstructorEnriched;
use Model\DTO\Payment\InstructorEnrichedFactory;
use Skautis\Skautis;

final class EducationInstructorEnrichmentQueryHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function __invoke(EducationInstructorEnrichmentQuery $query): InstructorEnriched
    {
        $skautisPerson = $this->skautis->OrganizationUnit->PersonDetail(['ID' => $query->getInstructor()->getPersonId()]);

        return InstructorEnrichedFactory::create($query->getInstructor(), $skautisPerson);
    }
}
