<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\Participant;

use function array_filter;

final class EducationParticipantListQueryHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    /** @return Participant[] */
    public function __invoke(EducationParticipantListQuery $query): array
    {
        $participants = $this->participants->findByEducation($query->getEducationId());

        if ($query->getOnlyAccepted()) {
            return array_filter(
                $participants,
                static function (Participant $participant) {
                    return $participant->isAccepted();
                },
            );
        }

        return $participants;
    }
}
