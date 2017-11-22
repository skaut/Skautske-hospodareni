<?php

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Doctrine\Common\Collections\ArrayCollection;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Common\AbstractAggregate;

class Cashbook extends AbstractAggregate
{

    /** @var int */
    private $id;

    /** @var ArrayCollection|Chit[] */
    private $chits;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->chits = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addChit(?ChitNumber $number, Date $date, ?Recipient $recipient, Amount $amount, string $purpose, int $categoryId): void
    {
        $this->chits[] = new Chit($this, $number, $date, $recipient, $amount, $purpose, $categoryId);
        $this->raise(new ChitWasAdded($this->id, $categoryId));
    }

    /**
     * @return float[] Category totals indexed by category IDs
     */
    public function getCategoryTotals(): array
    {
        $totalByCategories = [];

        foreach($this->chits as $chit) {
            $categoryId = $chit->getCategoryId();
            $totalByCategories[$categoryId]  = ($totalByCategories[$categoryId] ?? 0) + $chit->getAmount()->getValue();
        }

        return $totalByCategories;
    }

}
