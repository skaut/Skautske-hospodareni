<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Category as CategoryAggregate;
use Model\Cashbook\Operation;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_chits")
 */
class Chit
{
    /**
     * @var int|NULL
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var Cashbook
     * @ORM\ManyToOne(targetEntity=Cashbook::class, inversedBy="chits")
     * @ORM\JoinColumn(name="eventId")
     */
    private $cashbook;

    /**
     * @var ChitBody
     * @ORM\Embedded(class=ChitBody::class, columnPrefix=false)
     */
    private $body;

    /**
     * @var Category
     * @ORM\Embedded(class=Category::class, columnPrefix=false)
     */
    private $category;

    /**
     * ID of person that locked this
     *
     * @var int|NULL
     * @ORM\Column(type="integer", nullable=true, name="`lock`")
     */
    private $locked;

    public function __construct(Cashbook $cashbook, ChitBody $body, Category $category)
    {
        $this->cashbook = $cashbook;
        $this->update($body, $category);
    }

    public function update(ChitBody $body, Category $category) : void
    {
        $this->body     = $body;
        $this->category = $category;
    }

    public function lock(int $userId) : void
    {
        $this->locked = $userId;
    }

    public function unlock() : void
    {
        $this->locked = null;
    }

    public function getId() : int
    {
        if ($this->id === null) {
            throw new \RuntimeException('ID not set');
        }

        return $this->id;
    }

    public function getBody() : ChitBody
    {
        return $this->body;
    }

    public function getAmount() : Amount
    {
        return $this->body->getAmount();
    }

    public function getPurpose() : string
    {
        return $this->body->getPurpose();
    }

    public function getDate() : Date
    {
        return $this->body->getDate();
    }

    public function getCategoryId() : int
    {
        return $this->category->getId();
    }

    public function isLocked() : bool
    {
        return $this->locked !== null;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }

    public function getOperation() : Operation
    {
        return $this->category->getOperationType();
    }

    public function copyToCashbook(Cashbook $newCashbook) : self
    {
        return new self($newCashbook, $this->body, $this->category);
    }

    public function isIncome() : bool
    {
        return $this->category->getOperationType()->equalsValue(Operation::INCOME);
    }

    public function copyToCashbookWithUndefinedCategory(Cashbook $newCashbook) : self
    {
        $newChit = $this->copyToCashbook($newCashbook);

        $newChit->category = new Category(
            $newChit->isIncome() ? CategoryAggregate::UNDEFINED_INCOME_ID : CategoryAggregate::UNDEFINED_EXPENSE_ID,
            $newChit->category->getOperationType()
        );

        return $newChit;
    }
}
