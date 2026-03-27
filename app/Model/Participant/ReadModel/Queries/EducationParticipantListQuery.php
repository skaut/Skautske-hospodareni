<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\EducationParticipantListQueryHandler;
use App\Model\Event\SkautisEducationId;

/** @see EducationParticipantListQueryHandler */
final class EducationParticipantListQuery
{
    private SkautisEducationId $educationId;

    public function __construct(SkautisEducationId $id, private bool $onlyAccepted = true)
    {
        $this->educationId = $id;
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }

    public function getOnlyAccepted(): bool
    {
        return $this->onlyAccepted;
    }
}
