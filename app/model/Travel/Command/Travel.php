<?php

namespace Model\Travel\Command;

use Model\Travel\Command;

abstract class Travel
{

    /** @var int|NULL */
    private $id;

    /**
     * @internal - for mapping only
     * @var Command
     */
    private $command;

    /** @var TravelDetails */
    protected $details;

    protected function __construct(Command $command, TravelDetails $details)
    {
        $this->command = $command;
        $this->setDetails($details);
    }

    protected function setDetails(TravelDetails $details)
    {
        $this->details = $details;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDetails(): TravelDetails
    {
        return $this->details;
    }

}
