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

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $shortcut;

    /** @var Operation */
    private $operationType;

    /** @var bool */
    private $income;

    public function __construct(int $id, string $name, string $shortcut, Operation $operationType, bool $income)
    {
        $this->id = $id;
        $this->name = $name;
        $this->shortcut = $shortcut;
        $this->operationType = $operationType;
        $this->income = $income;
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
        return $this->income;
    }

}
