<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Command;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_travels")
 * @ORM\InheritanceType(value="SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="has_fuel", type="smallint")
 * @ORM\DiscriminatorMap(value={"1"=VehicleTravel::class, "0"=TransportTravel::class})
 */
abstract class Travel
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @internal - for mapping only
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Command::class, inversedBy="travels")
     * @ORM\JoinColumn(name="command_id", referencedColumnName="id", nullable=false)
     */
    private Command $command;

    /**
     * @ORM\Embedded(class=TravelDetails::class, columnPrefix=false)
     *
     * @var TravelDetails
     */
    private $details;

    protected function __construct(int $id, Command $command, TravelDetails $details)
    {
        $this->id      = $id;
        $this->command = $command;
        $this->setDetails($details);
    }

    protected function setDetails(TravelDetails $details) : void
    {
        $this->details = $details;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getDetails() : TravelDetails
    {
        return $this->details;
    }

    /**
     * @deprecated only for code completion
     */
    public function getCommand() : Command
    {
        return $this->command;
    }
}
