<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;

/**
 * @see CampParticipantIncomeQueryHandler
 */
final class CampParticipantIncomeQuery
{
    /** @var SkautisCampId */
    private $campId;

    /** @var bool */
    private $isAdult;

    /** @var bool */
    private $onAccount;

    public function __construct(SkautisCampId $id, bool $isAdult, bool $onAccount)
    {
        $this->campId    = $id;
        $this->isAdult   = $isAdult;
        $this->onAccount = $onAccount;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }

    public function isAdult() : bool
    {
        return $this->isAdult;
    }

    public function isOnAccount() : bool
    {
        return $this->onAccount;
    }
}
