<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventPragueParticipantsQuery;
use Model\Participant\PragueParticipants;
use Nette\Utils\Strings;

final class EventPragueParticipantsQueryHandler
{
    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(EventPragueParticipantsQuery $query) : ?PragueParticipants
    {
        if (! Strings::startsWith($query->getRegistrationNumber(), PragueParticipants::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        return PragueParticipants::fromParticipantList($query->getStartDate(), $this->queryBus->handle(new EventParticipantListQuery($query->getId())));
    }
}
