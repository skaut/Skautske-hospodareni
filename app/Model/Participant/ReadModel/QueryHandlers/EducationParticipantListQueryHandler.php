<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\Participant;

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
