<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;

/**
 * @see CampParticipantStatisticsQueryHandler
 */
final class CampParticipantStatisticsQuery
{
    /** @var SkautisCampId */
    private $id;

    public function __construct(SkautisCampId $id)
    {
        $this->id = $id;
    }

    public function getId() : SkautisCampId
    {
        return $this->id;
    }
}
