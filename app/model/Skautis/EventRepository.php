<?php

declare(strict_types=1);

namespace Model\Skautis;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Event\Event;
use Model\Event\EventNotFoundException;
use Model\Event\Repositories\IEventRepository;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use function array_column;
use function count;
use function is_null;
use function max;

final class EventRepository implements IEventRepository
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /** @var WebServiceInterface */
    private $webService;

    /** @var Mapper */
    private $mapper;

    private $skautisType = 'eventGeneral';

    public function __construct(WebServiceInterface $webService, Mapper $mapper)
    {
        $this->webService = $webService;
        $this->mapper     = $mapper;
    }

    public function find(int $skautisId) : Event
    {
        try {
            $skautisEvent = $this->webService->EventGeneralDetail(['ID' => $skautisId]);
            return $this->createEvent($skautisEvent);
        } catch (PermissionException $exc) {
            throw new EventNotFoundException($exc->getMessage(), $exc->getCode(), $exc);
        }
    }

    public function open(Event $event) : void
    {
        $skautisId = $this->getSkautisId($event->getId());
        $this->webService->EventGeneralUpdateOpen(['ID' => $skautisId], $this->skautisType);
    }

    public function close(Event $event) : void
    {
        $skautisId = $this->getSkautisId($event->getId());
        $this->webService->EventGeneralUpdateClose(['ID' => $skautisId], $this->skautisType);
    }

    public function update(Event $event) : void
    {
        $this->webService->eventGeneralUpdate([
            'ID' => $this->mapper->getSkautisId(CashbookId::fromInt($event->getId()), Mapper::EVENT), // @todo use Skautis ID instead of cashbook ID
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
            $this->mapper->getLocalId($skautisEvent->ID, Mapper::EVENT)->toInt(), // @todo Use Skautis ID instead of cashbook ID
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

    private function getSkautisId(int $id)
    {
        $skautisId = $this->mapper->getSkautisId(CashbookId::fromString((string) $id), Mapper::EVENT);
        if (is_null($skautisId)) {
            throw new EventNotFoundException();
        }
        return $skautisId;
    }
}
