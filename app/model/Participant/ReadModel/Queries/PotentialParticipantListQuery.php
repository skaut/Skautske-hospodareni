<?php

declare(strict_types=1);

namespace Model\Participant\ReadModel\Queries;

use Model\Common\UnitId;
use Model\DTO\Participant\Participant;
use Model\Participant\ReadModel\QueryHandlers\PotentialParticipantListQueryHandler;

/** @see PotentialParticipantListQueryHandler */
final class PotentialParticipantListQuery
{
    /** @param Participant[] $currentParticipants */
    public function __construct(private UnitId $unitId, private bool $directMembersOnly, private array $currentParticipants)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function directMembersOnly(): bool
    {
        return $this->directMembersOnly;
    }

    /** @return Participant[] */
    public function getCurrentParticipants(): array
    {
        return $this->currentParticipants;
    }
}
