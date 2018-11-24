<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\PragueParticipantsQuery;
use Model\Event\Repositories\IEventRepository;
use Model\Participant\Event;
use Model\Participant\PragueParticipants;
use Model\Participant\Repositories\IParticipantRepository;
use Nette\Utils\Strings;
use function stripos;

final class PragueParticipantsQueryHandler
{
    private const PRAGUE_SUPPORTABLE_AGE       = 18;
    private const PRAGUE_SUPPORTABLE_UPPER_AGE = 26;

    private const PRAGUE_UNIT_PREFIX = 11;

    /** @var IParticipantRepository */
    private $participants;

    /** @var IEventRepository */
    private $events;

    public function __construct(IParticipantRepository $participants, IEventRepository $events)
    {
        $this->participants = $participants;
        $this->events       = $events;
    }

    public function __invoke(PragueParticipantsQuery $query) : ?PragueParticipants
    {
        $event = $this->events->find($query->getEventId());

        if (! Strings::startsWith($event->getRegistrationNumber(), self::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        $participants = $this->participants->findByEvent(new Event(Event::GENERAL, $query->getEventId()->toInt()));

        $under18           = 0;
        $between18and26    = 0;
        $personDaysUnder26 = 0;
        $citizensCount     = 0;

        foreach ($participants as $p) {
            if (stripos($p->getCity(), 'Praha') === false) {
                continue;
            }

            $citizensCount++;

            if ($p->getBirthday() === null) {
                continue;
            }

            $ageInYears = $event->getStartDate()->diffInYears($p->getBirthday());

            if ($ageInYears <= self::PRAGUE_SUPPORTABLE_AGE) {
                $under18++;
            }

            if (self::PRAGUE_SUPPORTABLE_AGE < $ageInYears && $ageInYears <= self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                $between18and26++;
            }

            if ($ageInYears > self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                continue;
            }

            $personDaysUnder26 += $p->getDays();
        }
        return new PragueParticipants($under18, $between18and26, $personDaysUnder26, $citizensCount);
    }
}
