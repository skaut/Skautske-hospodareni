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


    public function getPrice(): Money
    {
        return $this->price;
    }

}
