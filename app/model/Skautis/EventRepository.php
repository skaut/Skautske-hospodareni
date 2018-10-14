<?php

declare(strict_types=1);

namespace Model\Skautis;

use Cake\Chronos\Date;
use Model\Event\Event;
use Model\Event\EventNotFound;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisEventId;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use function array_column;
use function count;
use function max;

final class EventRepository implements IEventRepository
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /** @var WebServiceInterface */
    private $webService;

    /** @var string */
    private $skautisType = 'eventGeneral';

    public function __construct(WebServiceInterface $webService)
    {
        $this->webService = $webService;
    }

    public function find(SkautisEventId $id) : Event
    {
        try {
            $skautisEvent = $this->webService->EventGeneralDetail(['ID' => $id->toInt()]);
            return $this->createEvent($skautisEvent);
        } catch (PermissionException $exc) {
            throw new EventNotFound($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function open(Event $event) : void
    {
        $this->webService->EventGeneralUpdateOpen(['ID' => $event->getId()->toInt()], $this->skautisType);
    }

    public function close(Event $event) : void
    {
        $this->webService->EventGeneralUpdateClose(['ID' => $event->getId()->toInt()], $this->skautisType);
    }

    public function update(Event $event) : void
    {
        $this->webService->eventGeneralUpdate([
            'ID' => $event->getId()->toInt(),
            'Location' => $event->getLocation(),
            'Note' => $event->getNote(),
            'ID_EventGeneralScope' => $event->getScopeId(),
            'ID_EventGeneralType' => $event->getTypeId(),
            'ID_Unit' => $event->getUnitId(),
            'DisplayName' => $event->getDisplayName(),
            'StartDate' => $event->getStartDate()->format('Y-m-d'),
            'EndDate' => $event->getEndDate()->format('Y-m-d'),
        ], 'eventGeneral');
    }

    public function getNewestEventId() : ?int
    {
        $events = $this->webService->eventGeneralAll(['IsRelation' => true]);

        $ids = array_column($events, 'ID');

        if (count($ids) === 0) {
            return null;
        }

        return max($ids);
    }

    private function createEvent(\stdClass $skautisEvent) : Event
    {
        return new Event(
            new SkautisEventId($skautisEvent->ID),
            $skautisEvent->DisplayName,
            $skautisEvent->ID_Unit,
            $skautisEvent->Unit,
            $skautisEvent->ID_EventGeneralState,
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisEvent->StartDate),
            Date::createFromFormat(self::DATETIME_FORMAT, $skautisEvent->EndDate),
            $skautisEvent->TotalDays,
            $skautisEvent->Location,
            $skautisEvent->RegistrationNumber,
            $skautisEvent->Note,
            $skautisEvent->ID_EventGeneralScope,
            $skautisEvent->ID_EventGeneralType
        );
    }
}
