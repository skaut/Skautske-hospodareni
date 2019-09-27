<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;

/**
 * @see ParticipantStatisticsQueryHandler
 */
final class ParticipantStatisticsQuery
{
    /** @var SkautisCampId|SkautisEventId */
    private $id;

    /**
     * @param SkautisCampId|SkautisEventId $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return SkautisCampId|SkautisEventId
     */
    public function getId()
    {
        return $this->id;
    }
}
