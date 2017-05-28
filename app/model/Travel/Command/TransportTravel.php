<?php

namespace Model\Travel\Command;

use Model\Travel\Command;
use Money\Money;

class TransportTravel extends Travel
{

    /** @var Money */
    private $price;

    public function __construct(Money $price, TravelDetails $details, Command $command)
    {
        parent::__construct($command, $details);
        $this->price = $price;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

}
