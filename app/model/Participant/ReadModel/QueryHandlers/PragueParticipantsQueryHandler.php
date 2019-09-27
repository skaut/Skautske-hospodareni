<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\PragueParticipantsQuery;
use Model\DTO\Participant\Participant as ParticipantDTO;
use Model\Event\SkautisEventId;
use Model\Participant\PragueParticipants;
use Nette\Utils\Strings;
use function assert;
use function stripos;

final class PragueParticipantsQueryHandler
{
    private const PRAGUE_SUPPORTABLE_AGE       = 18;
    private const PRAGUE_SUPPORTABLE_UPPER_AGE = 26;
    private const PRAGUE_UNIT_PREFIX           = 11;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(PragueParticipantsQuery $query) : ?PragueParticipants
    {
        if (! Strings::startsWith($query->getRegistrationNumber(), self::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        $eventStartDate    = $query->getStartDate();
        $participants      = $this->queryBus->handle(
            $query->getId() instanceof SkautisEventId
            ? new EventParticipantListQuery($query->getId())
            : new CampParticipantListQuery($query->getId())
        );
        $under18           = 0;
        $between18and26    = 0;
        $personDaysUnder26 = 0;
        $citizensCount     = 0;

        foreach ($participants as $p) {
            assert($p instanceof ParticipantDTO);
            if (stripos($p->getCity(), 'Praha') === false) {
                continue;
            }
            $citizensCount += 1;

            $birthday = $p->getBirthday();

            if ($birthday === null) {
                continue;
            }

            $ageInYears = $eventStartDate->diffInYears($birthday);

            if ($ageInYears <= self::PRAGUE_SUPPORTABLE_AGE) {
                $under18 += 1;
            }

            if (self::PRAGUE_SUPPORTABLE_AGE < $ageInYears && $ageInYears <= self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                $between18and26 += 1;
            }

            if ($ageInYears > self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                continue;
            }

            $personDaysUnder26 += $p->getDays();
        }

        return new PragueParticipants($under18, $between18and26, $personDaysUnder26, $citizensCount);
    }
}
