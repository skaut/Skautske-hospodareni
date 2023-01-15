<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Operation;
use Money\Money;
use Nette\SmartObject;

/**
 * @property-read int       $id
 * @property-read string    $name
 * @property-read Money     $total
 * @property-read Operation $operationType
 */
class CategorySummary
{
    use SmartObject;

    public function __construct(private int $id, private string $name, private Money $total, private Operation $operationType, private bool $virtual)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getOperationType(): Operation
    {
        return $this->operationType;
    }

    public function isIncome(): bool
    {
        return $this->operationType->equals(Operation::INCOME());
    }

    public function isVirtual(): bool
    {
        return $this->virtual;
    }
}
