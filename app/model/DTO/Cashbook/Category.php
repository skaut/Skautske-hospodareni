<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Operation;
use Nette\SmartObject;

/**
 * @property-read int       $id
 * @property-read string    $name
 * @property-read string    $shortcut
 * @property-read Operation $operationType
 */
class Category
{
    use SmartObject;

    private int $id;

    private string $name;

    private string $shortcut;

    private Operation $operationType;

    private bool $virtual;

    public function __construct(int $id, string $name, string $shortcut, Operation $operationType, bool $virtual)
    {
        $this->id            = $id;
        $this->name          = $name;
        $this->shortcut      = $shortcut;
        $this->operationType = $operationType;
        $this->virtual       = $virtual;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getShortcut() : string
    {
        return $this->shortcut;
    }

    public function getOperationType() : Operation
    {
        return $this->operationType;
    }

    public function isIncome() : bool
    {
        return $this->operationType->equals(Operation::INCOME());
    }

    public function isVirtual() : bool
    {
        return $this->virtual;
    }
}
