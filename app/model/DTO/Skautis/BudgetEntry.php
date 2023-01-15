<?php

declare(strict_types=1);

namespace Model\DTO\Skautis;

use Money\Money;
use Nette\SmartObject;

/**
 * @property-read string $name
 * @property-read Money $total
 */
final class BudgetEntry
{
    use SmartObject;

    public function __construct(private string $name, private Money $total, private bool $income)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function isIncome(): bool
    {
        return $this->income;
    }
}
