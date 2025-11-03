<?php

declare(strict_types=1);

namespace Extension\Doctrine\Entity\Partials;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping\Column;
use Extension\Doctrine\Types\CarbonTimestampImmutableMsType;

trait HasPreciseCreatedAt
{
    #[Column(type: CarbonTimestampImmutableMsType::NAME)]
    protected CarbonImmutable $createdAt;

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }
}
