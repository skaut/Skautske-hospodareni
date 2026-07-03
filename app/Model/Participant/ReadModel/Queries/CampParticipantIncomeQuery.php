<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Event\SkautisCampId;

/** @see CampParticipantIncomeQueryHandler */
final class CampParticipantIncomeQuery
{
    private SkautisCampId $campId;

    public function __construct(SkautisCampId $id, private ?bool $isAdult = null, private ?bool $onAccount = null)
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

    public function isAdult(): ?bool
    {
        return $this->isAdult;
    }

    public function isOnAccount(): ?bool
    {
        return $this->onAccount;
    }
}
