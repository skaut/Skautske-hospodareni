<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Assert\Assertion;
use Cake\Chronos\Date;
use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Category as CategoryAggregate;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Common\FilePath;
use Model\Common\ScanNotFound;
use Model\Common\ShouldNotHappen;
use RuntimeException;
use function array_map;
use function count;
use function implode;
use function in_array;
use function sprintf;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_chits")
 */
class Chit
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue()
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Cashbook::class, inversedBy="chits")
     * @ORM\JoinColumn(name="eventId")
     */
    private Cashbook $cashbook;

    /**
     * @ORM\Embedded(class=ChitBody::class, columnPrefix=false)
     */
    private ChitBody $body;

    /**
     * @ORM\ManyToMany(targetEntity=ChitItem::class, cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="ac_chit_to_item",
     *     joinColumns={@ORM\JoinColumn(name="chit_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     *     }
     * )
     *
     * @var ChitItem[]|ArrayCollection
     */
    private $items;

    /**
     * @ORM\Column(type="string_enum", length=13)
     *
     * @var PaymentMethod
     * @EnumAnnotation(class=PaymentMethod::class)
     */
    private $paymentMethod;

    /**
     * ID of person that locked this
     *
     * @ORM\Column(type="integer", nullable=true, name="`lock`", options={"unsigned"=true})
     */
    private ?int $locked;

    /**
     * @ORM\OneToMany(
     *     targetEntity=ChitScan::class,
     *     mappedBy="chit",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     *
     * @var ArrayCollection|ChitScan[]
     */
    private $scans;

    /**
     * @param ChitItem[] $items
     * @param ChitScan[] $scans
     */
    private function __construct(Cashbook $cashbook, ChitBody $body, PaymentMethod $paymentMethod, array $items, array $scans)
    {
        Assertion::notEmpty($items, 'At least one chit item was expected');

        $this->cashbook      = $cashbook;
        $this->body          = $body;
        $this->paymentMethod = $paymentMethod;
        $this->items         = new ArrayCollection($items);
        $this->scans         = new ArrayCollection(array_map(fn(ChitScan $scan) => new ChitScan($this, $scan->getFilePath()), $scans));
    }

    /**
     * @param ChitItem[]  $items
     * @param ICategory[] $categories
     */
    public static function create(Cashbook $cashbook, ChitBody $body, PaymentMethod $paymentMethod, array $items, array $categories) : self
    {
        self::validateItems($items, $categories);

        return new self($cashbook, $body, $paymentMethod, $items, []);
    }

    /**
     * @param ChitItem[]  $items
     * @param ICategory[] $categories
     */
    public function update(ChitBody $body, PaymentMethod $paymentMethod, array $items, array $categories) : void
    {
        Assertion::notEmpty($items, 'At least one chit item was expected');

        $this->body          = $body;
        $this->paymentMethod = $paymentMethod;
        self::validateItems($items, $categories);

        $this->items->clear();

        foreach ($items as $item) {
            $this->items->add($item);
        }
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
            throw new RuntimeException('ID not set');
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
            $exps[] = $item->getAmount()->getExpression();
        }

        return new Amount(implode('+', $exps));
    }

    public function getPurpose() : string
    {
        return implode(', ', $this->items->map(function (ChitItem $item) {
            return $item->getPurpose();
        })->toArray());
    }

    public function getDate() : Date
    {
        return $this->body->getDate();
    }

    public function getCategoryId() : int
    {
        if ($this->items->count() !== 1) {
            throw new ShouldNotHappen('This should be call just for chit with one item!');
        }

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
        return new self(
            $newCashbook,
            $this->body,
            $this->paymentMethod,
            $this->items->map(function (ChitItem $item) : ChitItem {
                return clone $item;
            })->toArray(),
            $this->scans->toArray()
        );
    }

    public function isIncome() : bool
    {
        return $this->getFirstItem()->getCategory()->getOperationType()->equalsValue(Operation::INCOME);
    }

    public function copyToCashbookWithUndefinedCategory(Cashbook $newCashbook) : self
    {
        $items = $this->items->map(function (ChitItem $item) : ChitItem {
            $category = new Category(
                $this->isIncome() ? CategoryAggregate::UNDEFINED_INCOME_ID : CategoryAggregate::UNDEFINED_EXPENSE_ID,
                $item->getCategory()->getOperationType()
            );

            return $item->withCategory($category);
        });

        $scans = $this->scans->toArray();

        return new self($newCashbook, $this->body, $this->paymentMethod, $items->toArray(), $scans);
    }

    public function withCategory(Category $category, Cashbook $cashbook) : self
    {
        $newItems = [];
        foreach ($this->items as $item) {
            $newItems[] = $item->withCategory($category);
        }

        return new self(
            $cashbook,
            $this->body->withoutChitNumber(),
            $this->paymentMethod,
            $newItems,
            $this->scans->toArray(),
        );
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
        return $this->items->first();
    }

    /**
     * @param ChitItem[]  $items
     * @param ICategory[] $categories
     */
    private static function validateItems(array $items, array $categories) : void
    {
        $itemCategories = [];
        foreach ($items as $item) {
            if (in_array($item->getCategory()->getId(), $itemCategories)) {
                throw new Cashbook\Chit\DuplicitCategory(sprintf('Category %d is duplicit', $item->getCategory()->getId()));
            }
            $itemCategories[] = $item->getCategory()->getId();

            if ($item->getCategory()->getOperationType()->equals(Operation::INCOME()) &&
                $item->getCategory()->getId() === ICategory::CATEGORY_HPD_ID &&
                count($items) > 1) {
                throw new Cashbook\Chit\SingleItemRestriction(sprintf('Chit with category %d should have just one item!', $item->getCategory()->getId()));
            }

            if ($categories[$item->getCategory()->getId()]->isVirtual() && count($items) > 1) {
                throw new Cashbook\Chit\SingleItemRestriction(sprintf('Chit with virtual category %d should have just one item!', $item->getCategory()->getId()));
            }
        }
    }

    public function addScan(FilePath $filePath) : void
    {
        $this->scans->add(new ChitScan($this, $filePath));
    }

    public function removeScan(FilePath $filePath) : void
    {
        foreach ($this->scans as $key => $scan) {
            if ($scan->getFilePath()->equals($filePath)) {
                $this->scans->remove($key);

                return;
            }
        }

        throw ScanNotFound::withPath($filePath);
    }

    /**
     * @return ChitScan[]
     */
    public function getScans() : array
    {
        return $this->scans->toArray();
    }

    public function removeAllScans() : void
    {
        $this->scans->clear();
    }
}
