<?php

namespace Model\Travel\Command;

use Model\Travel\Command;
use Money\Money;

class TransportTravel extends Travel
{

    /** @var Money */
    private $price;

    public function __construct(int $id, Money $price, TravelDetails $details, Command $command)
    {
        parent::__construct($id, $command, $details);
        $this->price = $price;
    }

    public function update(Money $price, TravelDetails $details): void
    {
        if( ! $price->isPositive()) {
            throw new \InvalidArgumentException("Price must be positive");
        }

        $this->price = $price;
        $this->setDetails($details);
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

}
