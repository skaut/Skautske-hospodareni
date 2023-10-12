<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationLocation;
use Model\Event\ReadModel\Queries\EducationLocationsQuery;
use Model\Skautis\Factory\EducationLocationFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

class EducationLocationsQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationLocationFactory $educationLocationFactory)
    {
    }

    /** @return array<int, EducationLocation> Education locations indexed by ID */
    public function __invoke(EducationLocationsQuery $query): array
    {
        $locations = $this->skautis->event->EventEducationLocationAll([
            'ID_EventEducation' => $query->getEventEducationId(),
        ]);

        if (is_object($locations)) {
            return [];
        }

        $result = [];
        foreach ($locations as $location) {
            $location = $this->educationLocationFactory->create($location);
            assert($location instanceof EducationLocation);

            $result[$location->getId()->toInt()] = $location;
        }

        return $result;
    }
}
