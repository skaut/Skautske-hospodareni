<?php

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Doctrine\Common\Collections\ArrayCollection;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasRemoved;
use Model\Cashbook\Events\ChitWasUpdated;
use Model\Common\AbstractAggregate;
use Nette\Utils\Strings;

class Cashbook extends AbstractAggregate
{

    /** @var CashbookId */
    private $id;

    /** @var CashbookType */
    private $type;

    /** @var string|NULL */
    private $chitNumberPrefix;

    /** @var ArrayCollection|Chit[] */
    private $chits;

    /** @var string|NULL */
    private $note;

    public function __construct(CashbookId $id, CashbookType $type)
    {
        $this->id = $id;
        $this->type = $type;
        $this->chits = new ArrayCollection();
    }

    public function getId(): CashbookId
    {
        return $this->id;
    }

    public function getType(): CashbookType
    {
        return $this->type;
    }

    public function getChitNumberPrefix(): ?string
    {
        return $this->chitNumberPrefix;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function updateChitNumberPrefix(?string $chitNumberPrefix): void
    {
        if ($chitNumberPrefix !== NULL && Strings::length($chitNumberPrefix) > 6) {
            throw new \InvalidArgumentException('Chit number prefix too long');
        }

        $this->chitNumberPrefix = $chitNumberPrefix;
    }

    public function updateNote(?string $note): void
    {
        $this->note = $note;
    }

    public function addChit(
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        ICategory $category
    ): void
    {
        $this->chits[] = new Chit($this, $number, $date, $recipient, $amount, $purpose, $this->getChitCategory($category));
        $this->raise(new ChitWasAdded($this->id, $category->getId()));
    }

    /**
     * Adds inverse chit for chit in specified cashbook
     * @throws InvalidCashbookTransferException
     */
    public function addInverseChit(Cashbook $cashbook, int $chitId): void
    {
        $originalChit = $cashbook->getChit($chitId);
        $originalCategoryId = $originalChit->getCategoryId();

        if ($this->type->getTransferToCategoryId() === $originalCategoryId) {
            // chit is transfer TO this cashbook
            $categoryId = $cashbook->type->getTransferFromCategoryId();
        } elseif ($this->type->getTransferFromCategoryId() === $originalCategoryId) {
            // chit is transfer FROM this cashbook
            $categoryId = $cashbook->type->getTransferToCategoryId();
        } else {
            throw new InvalidCashbookTransferException(
                "Can't create inverse chit to chit with category '$originalCategoryId'"
            );
        }

        $this->chits[] = new Chit(
            $this,
            NULL,
            $originalChit->getDate(),
            $originalChit->getRecipient(),
            $originalChit->getAmount(),
            $originalChit->getPurpose(),
            new Cashbook\Category($categoryId, $originalChit->getCategory()->getOperationType()->getInverseOperation())
        );

        $this->raise(new ChitWasAdded($this->id, $categoryId));
    }

    /**
     * @throws ChitNotFoundException
     * @throws ChitLockedException
     */
    public function updateChit(
        int $chitId,
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        ICategory $category
    ): void
    {
        $chit = $this->getChit($chitId);
        $oldCategoryId = $chit->getCategoryId();

        if($chit->isLocked()) {
            throw new ChitLockedException();
        }

        $chit->update($number, $date, $recipient, $amount, $purpose, $this->getChitCategory($category));

        $this->raise(new ChitWasUpdated($this->id, $oldCategoryId, $category->getId()));
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

    public function removeChit(int $chitId): void
    {
        $chit = $this->getChit($chitId);

        if($chit->isLocked()) {
            throw new ChitLockedException();
        }

        $this->chits->removeElement($chit);
        $this->raise(new ChitWasRemoved($this->id, $chit->getPurpose()));
    }

    public function lockChit(int $chitId, int $userId): void
    {
        $chit = $this->getChit($chitId);

        if($chit->isLocked()) {
            return;
        }

        $chit->lock($userId);
    }

    public function unlockChit(int $chitId): void
    {
        $chit = $this->getChit($chitId);

        if ( ! $chit->isLocked()) {
            return;
        }

        $chit->unlock();
    }

    public function lock(int $userId): void
    {
        foreach($this->chits as $chit) {
            if( ! $chit->isLocked()) {
                $chit->lock($userId);
            }
        }
    }

    /**
     * @throws ChitNotFoundException
     */
    public function copyChitsFrom(array $chitIds, Cashbook $sourceCashbook): void
    {
        $chits = array_map(function (int $chitId) use ($sourceCashbook): Chit {
            return $sourceCashbook->getChit($chitId);
        }, $chitIds);

        foreach ($chits as $chit) {
            /** @var Chit $chit */
            $newChit = $this->type->equals($sourceCashbook->type) && ! $this->type->equalsValue(CashbookType::CAMP)
                ? $chit->copyToCashbook($this)
                : $chit->copyToCashbookWithUndefinedCategory($this);

            $this->chits->add($newChit);

            $this->raise(new ChitWasAdded($this->id, $newChit->getCategoryId()));
        }
    }

    /**
     * Only for Read model
     * @return Chit[]
     */
    public function getChits(): array
    {
        return $this->chits
            ->map(function(Chit $c): Chit {
                // clone to avoid modification of cashbook
                return clone $c;
            })
            ->toArray();
    }

    public function clear(): void
    {
        $this->chits->clear();
    }

    /**
     * @throws ChitNotFoundException
     */
    private function getChit(int $id): Chit
    {
        foreach($this->chits as $chit) {
            if($chit->getId() === $id) {
                return $chit;
            }
        }

        throw new ChitNotFoundException();
    }

    private function getChitCategory(ICategory $category): Cashbook\Category
    {
        return new Cashbook\Category($category->getId(), $category->getOperationType());
    }

}
