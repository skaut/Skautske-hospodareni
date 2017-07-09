<?php

namespace Model\Chit\Events;

class ChitWasRemoved extends BaseChit
{
    /** @var  string */
    protected $chitPurpose;

    public function __construct(int $unitId, int $userId, string $userName, int $chitId, int $localId, string $chitPurpose)
    {
        parent::__construct($unitId, $userId, $userName, $chitId, $localId);
        $this->chitPurpose = $chitPurpose;
    }

    public function getChitPurpose(): string
    {
        return $this->chitPurpose;
    }
}
