<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitItem;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasRemoved;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Common\Aggregate;
use Model\Common\FilePath;
use Nette\Utils\Strings;
use function array_filter;
use function array_map;
use function assert;
use function max;
use function sprintf;
use function uasort;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_cashbook")
 */
class Cashbook extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="cashbook_id")
     */
    private CashbookId $id;

    /**
     * @ORM\Column(type="string_enum")
     *
     * @var CashbookType
     * @EnumAnnotation(class=CashbookType::class)
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $cashChitNumberPrefix = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $bankChitNumberPrefix = null;

    /**
     * @ORM\OneToMany(targetEntity=Chit::class, mappedBy="cashbook", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var ArrayCollection|Chit[]
     */
    private $chits;

    /**
     * @ORM\Column(type="text")
     */
    private string $note;

    public function __construct(CashbookId $id, CashbookType $type)
    {
        $this->id    = $id;
        $this->type  = $type;
        $this->chits = new ArrayCollection();
        $this->note  = '';
    }

    public function getId() : CashbookId
    {
        return $this->id;
    }

    public function getType() : CashbookType
    {
        return $this->type;
    }

    public function getCashChitNumberPrefix() : ?string
    {
        return $this->cashChitNumberPrefix;
    }

    public function getBankChitNumberPrefix() : ?string
    {
        return $this->bankChitNumberPrefix;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function updateChitNumberPrefix(?string $chitNumberPrefix, PaymentMethod $paymentMethod) : void
    {
        if ($chitNumberPrefix !== null && Strings::length($chitNumberPrefix) > 6) {
            throw new InvalidArgumentException('Chit number prefix too long');
        }

        if ($paymentMethod->equals(PaymentMethod::CASH())) {
            $this->cashChitNumberPrefix = $chitNumberPrefix;
        } else {
            $this->bankChitNumberPrefix = $chitNumberPrefix;
        }
    }

    public function updateNote(string $note) : void
    {
        $this->note = $note;
    }

    /**
     * @param ChitItem[]  $items
     * @param ICategory[] $categories
     */
    public function addChit(ChitBody $chitBody, PaymentMethod $paymentMethod, array $items, array $categories) : void
    {
        $this->chits->add(
            Chit::create(
                $this,
                $chitBody,
                $paymentMethod,
                $items,
                $this->reindexCategories($categories)
            )
        );
        $this->raise(new ChitWasAdded($this->id));
    }

    /**
     * Adds inverse chit for chit in specified cashbook
     *
     * @throws InvalidCashbookTransfer
     */
    public function addInverseChit(Cashbook $cashbook, int $chitId) : void
    {
        $originalChit       = $cashbook->getChit($chitId);
        $originalCategoryId = $originalChit->getCategoryId();

        if ($this->type->getTransferToCategoryId() === $originalCategoryId) {
            // chit is transfer TO this cashbook
            $categoryId = $cashbook->type->getTransferFromCategoryId();
        } elseif ($this->type->getTransferFromCategoryId() === $originalCategoryId) {
            // chit is transfer FROM this cashbook
            $categoryId = $cashbook->type->getTransferToCategoryId();
        } else {
            throw new InvalidCashbookTransfer(
                sprintf("Can't create inverse chit to chit with category '%s'", $originalCategoryId)
            );
        }

        $category = new Cashbook\Category(
            $categoryId,
            $originalChit->getOperation()->getInverseOperation()
        );

        $copy = $originalChit->withCategory($category, $this);
        $copy->removeAllScans();
        $this->chits->add($copy);

        $this->raise(new ChitWasAdded($this->id));
    }

    /**
     * @param ChitItem[]  $items
     * @param ICategory[] $categories
     *
     * @throws ChitNotFound
     * @throws ChitLocked
     */
    public function updateChit(int $chitId, ChitBody $chitBody, PaymentMethod $paymentMethod, array $items, array $categories) : void
    {
        $chit = $this->getChit($chitId);

        if ($chit->isLocked()) {
            throw new ChitLocked();
        }

        $chit->update($chitBody, $paymentMethod, $items, $this->reindexCategories($categories));

        $this->raise(new ChitWasUpdated($this->id));
    }

    /**
     * @return float[] Category totals indexed by category IDs
     */
    public function getCategoryTotals() : array
    {
        $totalByCategories = [];

        foreach ($this->chits as $chit) {
            foreach ($chit->getItems() as $item) {
                $categoryId                     = $item->getCategory()->getId();
                $totalByCategories[$categoryId] = ($totalByCategories[$categoryId] ?? 0) + $item->getAmount()->toFloat();
            }
        }

        return $totalByCategories;
    }

    public function removeChit(int $chitId) : void
    {
        $chit = $this->getChit($chitId);

        if ($chit->isLocked()) {
            throw new ChitLocked();
        }

        $this->chits->removeElement($chit);
        $this->raise(new ChitWasRemoved($this->id, $chit->getPurpose()));
    }

    public function lockChit(int $chitId, int $userId) : void
    {
        $chit = $this->getChit($chitId);

        if ($chit->isLocked()) {
            return;
        }

        $chit->lock($userId);
    }

    public function unlockChit(int $chitId) : void
    {
        $chit = $this->getChit($chitId);

        if (! $chit->isLocked()) {
            return;
        }

        $chit->unlock();
    }

    public function lock(int $userId) : void
    {
        foreach ($this->chits as $chit) {
            if ($chit->isLocked()) {
                continue;
            }

            $chit->lock($userId);
        }
    }

    /**
     * @param int[] $chitIds
     *
     * @throws ChitNotFound
     */
    public function copyChitsFrom(array $chitIds, Cashbook $sourceCashbook) : void
    {
        $chits = array_map(
            function (int $chitId) use ($sourceCashbook) : Chit {
                return $sourceCashbook->getChit($chitId);
            },
            $chitIds
        );

        foreach ($chits as $chit) {
            assert($chit instanceof Chit);

            $newChit = $this->type->equals($sourceCashbook->type) && ! $this->type->equalsValue(CashbookType::CAMP)
                ? $chit->copyToCashbook($this)
                : $chit->copyToCashbookWithUndefinedCategory($this);

            $this->chits->add($newChit);

            $this->raise(new ChitWasAdded($this->id));
        }
    }

    /**
     * Only for Read model
     *
     * @return Chit[]
     */
    public function getChits(?PaymentMethod $paymentMethodOnly = null) : array
    {
        return array_map(
            function (Chit $chit) : Chit {
                return clone $chit;
            },
            $this->sortedChits($paymentMethodOnly)
        );
    }

    public function clear() : void
    {
        $this->chits->clear();
    }

    /**
     * @throws ChitNotFound
     */
    private function getChit(int $id) : Chit
    {
        foreach ($this->chits as $chit) {
            if ($chit->getId() === $id) {
                return $chit;
            }
        }

        throw new ChitNotFound();
    }

    /**
     * @throws MaxChitNumberNotFound
     * @throws NonNumericChitNumbers
     */
    private function getMaxChitNumber(PaymentMethod $paymentMethod) : int
    {
        if (! $this->hasOnlyNumericChitNumbers()) {
            throw new NonNumericChitNumbers();
        }

        $defaultMax = -1;
        $res        = $defaultMax;

        foreach ($this->chits as $chit) {
            $number = $chit->getBody()->getNumber();

            if ($number === null || ! $chit->getPaymentMethod()->equals($paymentMethod)) {
                continue;
            }

            $res = max($res, (int) $number->toString());
        }

        if ($res === $defaultMax) {
            throw new MaxChitNumberNotFound();
        }

        return $res;
    }

    public function hasOnlyNumericChitNumbers() : bool
    {
        foreach ($this->chits as $chit) {
            $number = $chit->getBody()->getNumber();

            if ($number !== null && $number->containsLetter()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws MaxChitNumberNotFound
     * @throws NonNumericChitNumbers
     */
    public function generateChitNumbers(PaymentMethod $paymentMethod) : void
    {
        $maxChitNumber = $this->getMaxChitNumber($paymentMethod);

        foreach ($this->sortedChits($paymentMethod) as $chit) {
            $body = $chit->getBody();

            if ($body->getNumber() !== null || $chit->isLocked()) {
                continue;
            }

            $newBody = $body->withNewNumber(new ChitNumber((string) (++$maxChitNumber)));

            $chit->setBody($newBody);
            $this->raise(new ChitWasUpdated($this->id));
        }
    }

    public function addChitScan(int $chitId, FilePath $path) : void
    {
        $chit = $this->getChit($chitId);
        $chit->addScan($path);
    }

    public function removeChitScan(int $chitId, FilePath $path) : void
    {
        $chit = $this->getChit($chitId);
        $chit->removeScan($path);
    }

    /**
     * @param ICategory[] $categories
     *
     * @return ICategory[]
     */
    private function reindexCategories(array $categories) : array
    {
        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = $category;
        }

        return $categoriesById;
    }

    /**
     * @return Chit[]
     */
    private function sortedChits(?PaymentMethod $paymentMethodOnly) : array
    {
        $chits = $this->chits->toArray();

        if ($paymentMethodOnly !== null) {
            $chits = array_filter(
                $chits,
                function (Chit $chit) use ($paymentMethodOnly) : bool {
                    return $chit->getPaymentMethod()->equals($paymentMethodOnly);
                }
            );
        }

        uasort($chits, function (Chit $first, Chit $second) : int {
            return [
                $first->getDate(),
                $first->getOperation()->toString(),
                $first->getId(),
            ] <=> [
                $second->getDate(),
                $second->getOperation()->toString(),
                $second->getId(),
            ];
        });

        return $chits;
    }
}
