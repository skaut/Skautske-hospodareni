<?php

declare(strict_types=1);

namespace Model\DTO\Budget;

use Nette\SmartObject;

/**
 * @property-read string $label
 * @property-read float $value
 * @property-read Category[] $children
 */
class Category
{
    use SmartObject;

    private int $id;

    private string $label;

    /** @var Category[] */
    private array $children;

    private float $value;

    /**
     * @param Category[] $children
     */
    public function __construct(int $id, string $label, float $value, array $children)
    {
        $this->id       = $id;
        $this->label    = $label;
        $this->value    = $value;
        $this->children = $children;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function getValue() : float
    {
        return $this->value;
    }

    /**
     * @return Category[]
     */
    public function getChildren() : array
    {
        return $this->children;
    }
}
