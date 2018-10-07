<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use Model\Travel\Command;
use Model\Utils\MoneyFactory;
use Money\Money;
use function sprintf;

class TransportTravel extends Travel
{
    /** @var Money */
    private $price;

    public function __construct(int $id, Money $price, TravelDetails $details, Command $command)
    {
        parent::__construct($id, $command, $details);
        $this->setPrice($price);
    }

    public function update(Money $price, TravelDetails $details) : void
    {
        $this->setPrice($price);
        $this->setDetails($details);
    }

    public function getPrice() : Money
    {
        return $this->price;
    }

    private function setPrice(Money $price) : void
    {
        if (! $price->isPositive()) {
            throw new \InvalidArgumentException(
                sprintf('Price must be positive number, %01.2f given', MoneyFactory::toFloat($price))
            );
        }

        $this->price = $price;
    }
}
