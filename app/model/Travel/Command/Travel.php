<?php

namespace Model\Travel\Command;

use Model\Travel\Command;

abstract class Travel
{

    /** @var int */
    private $id;

    /**
     * @internal - for mapping only
     * @var Command
     */
    private $command;

    /** @var TravelDetails */
    protected $details;

    protected function __construct(int $id, Command $command, TravelDetails $details)
    {
        $this->id = $id;
        $this->command = $command;
        $this->setDetails($details);
    }

    protected function setDetails(TravelDetails $details): void
    {
        $this->details = $details;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDetails(): TravelDetails
    {
        return $this->details;
    }

}
