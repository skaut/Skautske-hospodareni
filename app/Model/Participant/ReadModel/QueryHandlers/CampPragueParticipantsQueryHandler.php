<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\CampPragueParticipantsQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\Participant\PragueParticipants;
use Nette\Utils\Strings;

final class CampPragueParticipantsQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(CampPragueParticipantsQuery $query): ?PragueParticipants
    {
        if (! Strings::startsWith($query->getRegistrationNumber(), PragueParticipants::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        return PragueParticipants::fromParticipantList($query->getStartDate(), $this->queryBus->handle(new CampParticipantListQuery($query->getId())));
    }
}
