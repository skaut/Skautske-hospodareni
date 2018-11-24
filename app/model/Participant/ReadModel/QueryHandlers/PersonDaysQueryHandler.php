<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\PersonDaysQuery;
use Model\Participant\Repositories\IParticipantRepository;

/**
 * Vrací počet osobodnů pro danou akci
 */
final class PersonDaysQueryHandler
{
    /** @var IParticipantRepository */
    private $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    public function __invoke(PersonDaysQuery $query) : int
    {
        $personDays = 0;

        foreach ($this->participants->findByEvent($query->getEventType(), $query->getEventId()) as $participant) {
            $personDays += $participant->getDays();
        }

        return $personDays;
    }
}
