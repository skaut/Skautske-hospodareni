<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\Skautis\Factory\CampFactory;
use Skautis\Skautis;
use function is_object;

class CampListQueryHandler
{
    /** @var Skautis */
    private $skautis;

    /** @var CampFactory */
    private $campFactory;

    public function __construct(Skautis $skautis, CampFactory $campFactory)
    {
        $this->skautis     = $skautis;
        $this->campFactory = $campFactory;
    }

    /**
     * @return array<int, Camp> Camps indexed by ID
     */
    public function handle(CampListQuery $query) : array
    {
        $camps = $this->skautis->event->EventCampAll([
            'Year' => $query->getYear(),
        ]);

        if (is_object($camps)) {
            return [];
        }

        $result = [];

        foreach ($camps as $camp) {
            $camp = $this->campFactory->create($camp);

            $result[$camp->getId()->toInt()] = $camp;
        }

        return $camps;
    }
}
