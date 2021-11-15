<?php

declare(strict_types=1);

namespace Model\Skautis\ReadModel\QueryHandlers;

use Model\DTO\Event\StatisticsItem;
use Model\Skautis\ReadModel\Queries\EventStatisticsQuery;
use Skautis\Wsdl\WebServiceInterface;

final class EventStatisticsQueryHandler
{
    private WebServiceInterface $eventWebService;

    public function __construct(WebServiceInterface $eventWebService)
    {
        $this->eventWebService = $eventWebService;
    }

    /**
     * @return StatisticsItem[]
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function __invoke(EventStatisticsQuery $query): array
    {
        $skautisData = $this->eventWebService->EventStatisticAllEventGeneral(['ID_EventGeneral' => $query->getEventId()->toInt()]);

        $result = [];

        foreach ($skautisData as $row) {
            $result[$row->ID_ParticipantCategory] = new StatisticsItem($row->ParticipantCategory, $row->Count);
        }

        return $result;
    }
}
