<?php

namespace Model\DTO\Travel\Command;

use Model\Travel\Command\TravelDetails;
use Money\Money;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read TravelDetails $details
 * @property-read float|NULL $distance
 * @property-read Money $price
 */
class Travel
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var TravelDetails */
    private $details;

    /** @var float|NULL */
    private $distance;

    /** @var Money */
    private $price;

    public function __construct(int $id, TravelDetails $details, ?float $distance, Money $price)
    {
        $this->id = $id;
        $this->details = $details;
        $this->distance = $distance;
        $this->price = $price;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDetails(): TravelDetails
    {
        return $this->details;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

}
