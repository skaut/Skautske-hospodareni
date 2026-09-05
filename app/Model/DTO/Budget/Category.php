<?php

declare(strict_types=1);

namespace App\Model\DTO\Budget;

use Money\Money;
use Nette\SmartObject;

/**
 * @property string     $label
 * @property Money      $value
 * @property Category[] $children
 */
class Category
{
    use SmartObject;

    /** @param Category[] $children */
    public function __construct(private int $id, private string $label, private Money $value, private array $children)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): Money
    {
        return $this->value;
    }

    /** @return Category[] */
    public function getChildren(): array
    {
        return $this->children;
    }
}
