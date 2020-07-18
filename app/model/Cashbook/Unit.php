<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Events\Unit\CashbookWasCreated;
use Model\Cashbook\Exception\YearCashbookAlreadyExists;
use Model\Cashbook\Unit\Cashbook;
use Model\Common\Aggregate;
use Model\Common\ShouldNotHappen;
use Model\Common\UnitId;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_units")
 */
class Unit extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="unit_id")
     */
    private UnitId $id;

    /**
     * @ORM\OneToMany(targetEntity=Cashbook::class, mappedBy="unit", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection|Cashbook[]
     */
    private $cashbooks;

    /** @ORM\Column(type="integer") */
    private int $activeCashbookId;

    /** @ORM\Column(type="integer") */
    private int $nextCashbookId = 1;

    public function __construct(UnitId $id, CashbookId $activeCashbookId, int $activeCashbookYear)
    {
        $cashbook = new Cashbook($this->getCashbookId(), $this, $activeCashbookYear, $activeCashbookId);

        $this->id               = $id;
        $this->cashbooks        = new ArrayCollection([$cashbook]);
        $this->activeCashbookId = $cashbook->getId();
        $this->raise(new CashbookWasCreated($this->id, $activeCashbookId));
    }

    /**
     * @see CashbookWasCreated - event raised on success
     *
     * @throws YearCashbookAlreadyExists
     */
    public function createCashbook(int $year) : void
    {
        if ($this->cashbookForYearExists($year)) {
            throw YearCashbookAlreadyExists::forYear($year, $this->id);
        }

        $cashbookId = CashbookId::generate();
        $this->cashbooks->add(new Cashbook($this->getCashbookId(), $this, $year, $cashbookId));

        $this->raise(new CashbookWasCreated($this->id, $cashbookId));
    }

    public function getId() : UnitId
    {
        return $this->id;
    }

    public function activateCashbook(int $cashbookId) : void
    {
        if ($cashbookId >= $this->nextCashbookId) {
            throw UnitCashbookNotFound::withId($cashbookId, $this->id);
        }

        $this->activeCashbookId = $cashbookId;
    }

    /**
     * @return Unit\Cashbook[]
     */
    public function getCashbooks() : array
    {
        return $this->cashbooks->toArray();
    }

    public function getActiveCashbook() : Unit\Cashbook
    {
        foreach ($this->cashbooks as $cashbook) {
            if ($cashbook->getId() === $this->activeCashbookId) {
                return $cashbook;
            }
        }

        throw new ShouldNotHappen('Unit always should have active cashbook set');
    }

    private function cashbookForYearExists(int $year) : bool
    {
        return $this->cashbooks->exists(function ($_x, Cashbook $cashbook) use ($year) : bool {
            return $cashbook->getYear() === $year;
        });
    }

    private function getCashbookId() : int
    {
        return $this->nextCashbookId++;
    }
}
