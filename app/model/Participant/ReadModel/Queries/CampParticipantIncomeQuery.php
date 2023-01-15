<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;

/** @see CampParticipantIncomeQueryHandler */
final class CampParticipantIncomeQuery
{
    private SkautisCampId $campId;

    public function __construct(SkautisCampId $id, private bool|null $isAdult = null, private bool|null $onAccount = null)
    {
        $this->campId = $id;
    }

    public static function all(SkautisCampId $id): self
    {
        return new self($id, null, null);
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

    public function isAdult(): bool|null
    {
        return $this->isAdult;
    }

    public function isOnAccount(): bool|null
    {
        return $this->onAccount;
    }
}
