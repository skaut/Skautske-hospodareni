<?php

declare(strict_types=1);

namespace App\Model\DTO\Cashbook;

use App\Model\Cashbook\Operation;
use Nette\SmartObject;

/**
 * @property int       $id
 * @property string    $name
 * @property string    $shortcut
 * @property Operation $operationType
 */
class Category
{
    use SmartObject;

    public function __construct(private int $id, private string $name, private string $shortcut, private Operation $operationType, private bool $virtual)
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

    public function getShortcut(): string
    {
        return $this->shortcut;
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
