<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Cake\Chronos\Date;
use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Category as CategoryAggregate;
use Model\Cashbook\Operation;
use Model\Common\ShouldNotHappen;
use function count;
use function implode;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_chits")
 */
class Chit
{
    /**
     * @var int|NULL
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned"=true})
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
     * @var ChitItem[]|ArrayCollection
     * @ORM\OneToMany(targetEntity=ChitItem::class, mappedBy="chit")
     */
    private $items;

    /**
     * @var PaymentMethod
     * @ORM\Column(type="string_enum", length=13)
     * @EnumAnnotation(class=PaymentMethod::class)
     */
    private $paymentMethod;

    /**
     * ID of person that locked this
     *
     * @var int|NULL
     * @ORM\Column(type="integer", nullable=true, name="`lock`", options={"unsigned"=true})
     */
    private $locked;

    public function __construct(Cashbook $cashbook, ChitBody $body, PaymentMethod $paymentMethod)
    {
        $this->items         = new ArrayCollection();
        $this->cashbook      = $cashbook;
        $this->body          = $body;
        $this->paymentMethod = $paymentMethod;
    }

    public function addItem(Amount $amount, Category $category) : void
    {
        $this->items[] = new ChitItem($this, $amount, $category);
    }

    public function update(ChitBody $body, Category $category, PaymentMethod $paymentMethod, Amount $amount) : void
    {
        $this->body = $body;
        $this->getFirstItem()->setCategory($category);
        $this->getFirstItem()->setAmount($amount);
        $this->paymentMethod = $paymentMethod;
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
        $exps = [];
        foreach ($this->items as $item) {
            $exps[] =$item->getAmount()->expression;
        }
        return new Amount(implode('+', $exps));
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
        return $this->getFirstItem()->getCategory()->getId();
    }

    public function isLocked() : bool
    {
        return $this->locked !== null;
    }

    public function getOperation() : Operation
    {
        return $this->getFirstItem()->getCategory()->getOperationType();
    }

    public function copyToCashbook(Cashbook $newCashbook) : self
    {
        $chit = new self($newCashbook, $this->body, $this->paymentMethod);
        $chit->addItem($this->getFirstItem()->getAmount(), $this->getFirstItem()->getCategory());
        return $chit;
    }

    public function isIncome() : bool
    {
        return $this->getFirstItem()->getCategory()->getOperationType()->equalsValue(Operation::INCOME);
    }

    public function copyToCashbookWithUndefinedCategory(Cashbook $newCashbook) : self
    {
        $newChit = $this->copyToCashbook($newCashbook);

        $newChit->getFirstItem()->setCategory(new Category(
            $newChit->isIncome() ? CategoryAggregate::UNDEFINED_INCOME_ID : CategoryAggregate::UNDEFINED_EXPENSE_ID,
            $newChit->getFirstItem()->getCategory()->getOperationType()
        ));

        return $newChit;
    }

    public function getPaymentMethod() : PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @return ChitItem[]
     */
    public function getItems() : array
    {
        return $this->items->toArray();
    }

    public function setBody(ChitBody $body) : void
    {
        $this->body = $body;
    }


    private function getFirstItem() : ChitItem
    {
        if (count($this->items) === 0) {
            throw new ShouldNotHappen();
        }
        return $this->items->first();
    }
}
