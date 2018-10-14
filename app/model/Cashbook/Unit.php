<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Unit\Cashbook;
use Model\Common\ShouldNotHappen;
use Model\Common\UnitId;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_units")
 */
class Unit
{
    /**
     * @var UnitId
     * @ORM\Id()
     * @ORM\Column(type="unit_id")
     */
    private $id;

    /**
     * @var ArrayCollection|Cashbook[]
     * @ORM\OneToMany(targetEntity=Cashbook::class, mappedBy="unit", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $cashbooks;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $activeCashbookId;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $nextCashbookId = 1;

    public function __construct(UnitId $id, CashbookId $activeCashbookId, int $activeCashbookYear)
    {
        $cashbook = new Cashbook($this->getCashbookId(), $this, $activeCashbookYear, $activeCashbookId);

        $this->id               = $id;
        $this->cashbooks        = new ArrayCollection([$cashbook]);
        $this->activeCashbookId = $cashbook->getId();
    }

    public function getId() : UnitId
    {
        return $this->id;
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

    private function getCashbookId() : int
    {
        return $this->nextCashbookId++;
    }
}
