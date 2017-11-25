<?php

namespace Model\Cashbook\Events;

/**
 * @todo Use this event for logging
 */
class ChitWasRemoved
{

    /** @var int */
    private $cashbookId;

    /** @var string */
    private $chitPurpose;

    public function __construct(int $cashbookId, string $chitPurpose)
    {
        $this->cashbookId = $cashbookId;
        $this->chitPurpose = $chitPurpose;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getChitPurpose(): string
    {
        return $this->chitPurpose;
    }

}
