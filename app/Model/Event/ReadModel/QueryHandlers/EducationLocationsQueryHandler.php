<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationLocation;
use App\Model\Event\ReadModel\Queries\EducationLocationsQuery;
use App\Model\Skautis\Factory\EducationLocationFactory;
use LogicException;
use Skautis\Skautis;

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
            if (! $location instanceof EducationLocation) {
                throw new LogicException('Assertion failed.');
            }
            $result[$location->getId()->toInt()] = $location;
        }

        return $result;
    }
}
