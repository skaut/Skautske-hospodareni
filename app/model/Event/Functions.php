<?php

namespace Model\Event;

class Functions
{

    /** @var int|NULL */
    private $leaderId;

    /** @var int|NULL */
    private $assistantId;

    /** @var int|NULL */
    private $accountantId;

    /** @var int|NULL */
    private $medicId;

    public function __construct(?int $leaderId, ?int $assistantId, ?int $accountantId, ?int $medicId)
    {
        $this->leaderId = $leaderId;
        $this->assistantId = $assistantId;
        $this->accountantId = $accountantId;
        $this->medicId = $medicId;
    }


    public function getLeaderId(): ?int
    {
        return $this->leaderId;
    }

    public function getAssistantId(): ?int
    {
        return $this->assistantId;
    }

    public function getAccountantId(): ?int
    {
        return $this->accountantId;
    }

    public function getMedicId(): ?int
    {
        return $this->medicId;
    }

}
