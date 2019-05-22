<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook\Form;

use Model\Cashbook\Cashbook\Amount;

class ChitItem
{
    /** @var int|null */
    private $id;

    /** @var Amount */
    private $amount;

    /** @var int */
    private $category;

    /** @var string */
    private $purpose;

    public function __construct(?int $id, Amount $amount, int $category, string $purpose)
    {
        $this->id       = $id;
        $this->amount   = $amount;
        $this->category = $category;
        $this->purpose  = $purpose;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategory() : int
    {
        return $this->category;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }
}
