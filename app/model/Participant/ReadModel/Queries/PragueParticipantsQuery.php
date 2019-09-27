<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Cake\Chronos\Date;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;

/**
 * @see ParticipantStatisticsQueryHandler
 */
final class PragueParticipantsQuery
{
    /** @var SkautisEventId|SkautisCampId */
    private $id;

    /** @var string */
    private $registrationNumber;

    /** @var Date */
    private $startDate;

    /**
     * @param SkautisEventId|SkautisCampId $id
     */
    public function __construct($id, string $registrationNumber, Date $startDate)
    {
        $this->id                 = $id;
        $this->registrationNumber = $registrationNumber;
        $this->startDate          = $startDate;
    }

    /**
     * @return SkautisCampId|SkautisEventId
     */
    public function getId()
    {
        return $this->id;
    }

    public function getRegistrationNumber() : string
    {
        return $this->registrationNumber;
    }

    public function getStartDate() : Date
    {
        return $this->startDate;
    }
}
