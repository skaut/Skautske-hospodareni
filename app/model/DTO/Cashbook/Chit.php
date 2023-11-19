<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Cake\Chronos\ChronosDate;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\ChitScan;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Operation;
use Nette\SmartObject;

use function array_map;
use function count;
use function implode;
use function sprintf;
use function substr;

/**
 * @property-read int               $id
 * @property-read ChitBody          $body
 * @property-read ChitNumber|NULL   $number
 * @property-read ChronosDate              $date
 * @property-read Recipient|NULL    $recipient
 * @property-read Amount            $amount
 * @property-read string            $purpose
 * @property-read ChitItem[]        $items
 * @property-read bool              $locked
 * @property-read CashbookType[]    $inverseCashbookTypes
 * @property-read PaymentMethod     $paymentMethod
 * @property-read string            $categories
 * @property-read string            $categoriesShortcut
 * @property-read int               $scansCount
 */
class Chit
{
    use SmartObject;

    /**
     * @param CashbookType[] $inverseCashbookTypes
     * @param ChitItem[]     $items
     * @param ChitScan[]     $scans
     */
    public function __construct(
        private int $id,
        private ChitBody $body,
        private bool $locked,
        private array $inverseCashbookTypes,
        private PaymentMethod $paymentMethod,
        private array $items,
        private Operation $operation,
        private Amount $amount,
        private array $scans,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBody(): ChitBody
    {
        return $this->body;
    }

    /** @deprecated use getBody() */
    public function getNumber(): ChitNumber|null
    {
        return $this->body->getNumber();
    }

    /** @deprecated use getBody() */
    public function getDate(): ChronosDate
    {
        return $this->body->getDate();
    }

    /** @deprecated use getBody() */
    public function getRecipient(): Recipient|null
    {
        return $this->body->getRecipient();
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getPurpose(): string
    {
        return implode(', ', array_map(function (ChitItem $i) {
            return $i->getPurpose();
        }, $this->items));
    }

    public function isVirtual(): bool
    {
        return $this->items[0]->getCategory()->isVirtual();
    }

    public function isHpd(): bool
    {
        return $this->items[0]->getCategory()->getShortcut() === 'hpd';
    }

    public function getCategories(): string
    {
        return implode(', ', array_map(function (ChitItem $item) {
            return $item->getCategory()->getName();
        }, $this->items));
    }

    public function getCategoriesShortcut(): string
    {
        return implode(', ', array_map(function (ChitItem $item) {
            return $item->getCategory()->getShortcut();
        }, $this->items));
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /** @return CashbookType[] */
    public function getInverseCashbookTypes(): array
    {
        return $this->inverseCashbookTypes;
    }

    public function isIncome(): bool
    {
        return $this->operation->equalsValue(Operation::INCOME);
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getSignedAmount(): float
    {
        $amount = $this->amount->toFloat();

        if ($this->operation->equalsValue(Operation::EXPENSE)) {
            return -1 * $amount;
        }

        return $amount;
    }

    /** @return ChitItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @return ChitScan[] */
    public function getScans(): array
    {
        return $this->scans;
    }

    public function getScansCount(): int
    {
        return count($this->scans);
    }

    public function getName(): string
    {
        return sprintf(
            '%s_%s',
            $this->getBody()->getDate()->format('Y-m-d'),
            substr($this->getPurpose(), 0, 30),
        );
    }
}
