<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;

/**
 * @see CampParticipantIncomeQueryHandler
 */
final class CampParticipantIncomeQuery
{
    private SkautisCampId $campId;

    /** @var bool|null */
    private $isAdult;

    /** @var bool|null */
    private $onAccount;

    public function __construct(SkautisCampId $id, ?bool $isAdult, ?bool $onAccount)
    {
        $this->campId    = $id;
        $this->isAdult   = $isAdult;
        $this->onAccount = $onAccount;
    }

    public static function all(SkautisCampId $id) : self
    {
        return new self($id, null, null);
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }

    public function isAdult() : ?bool
    {
        return $this->isAdult;
    }

    public function isOnAccount() : ?bool
    {
        return $this->onAccount;
    }
}
