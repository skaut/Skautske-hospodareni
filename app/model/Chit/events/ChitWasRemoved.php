<?php

declare(strict_types=1);

namespace Model\Chit\Events;

class ChitWasRemoved extends BaseChit
{
    public function __construct(int $unitId, int $userId, string $userName, int $chitId, int $localId, private string $chitPurpose)
    {
        parent::__construct($unitId, $userId, $userName, $chitId, $localId);
    }

    public function getChitPurpose(): string
    {
        return $this->chitPurpose;
    }
}
