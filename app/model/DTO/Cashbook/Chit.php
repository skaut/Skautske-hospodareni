<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Category;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Nette\SmartObject;

/**
 * @property-read int               $id
 * @property-read ChitNumber|NULL   $number
 * @property-read Date              $date
 * @property-read Recipient|NULL    $recipient
 * @property-read Amount            $amount
 * @property-read string            $purpose
 * @property-read Category          $category
 * @property-read bool              $locked
 * @property-read CashbookType[]    $inverseCashbookTypes
 */
class Chit
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var ChitNumber|NULL */
    private $number;

    /** @var Date */
    private $date;

    /** @var Recipient|NULL */
    private $recipient;

    /** @var Amount */
    private $amount;

    /** @var string */
    private $purpose;

    /** @var Category */
    private $category;

    /** @var bool */
    private $locked;

    /** @var CashbookType[] */
    private $inverseCashbookTypes;

    /**
     * @param CashbookType[] $inverseCashbookTypes
     */
    public function __construct(
        int $id,
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        Category $category,
        bool $locked,
        array $inverseCashbookTypes
    )
    {
        $this->id = $id;
        $this->number = $number;
        $this->date = $date;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->purpose = $purpose;
        $this->category = $category;
        $this->locked = $locked;
        $this->inverseCashbookTypes = $inverseCashbookTypes;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNumber(): ?ChitNumber
    {
        return $this->number;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @return CashbookType[]
     */
    public function getInverseCashbookTypes(): array
    {
        return $this->inverseCashbookTypes;
    }

}
