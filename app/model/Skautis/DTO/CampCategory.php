<?php

declare(strict_types=1);

namespace Model\Skautis\DTO;

use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;

final class CampCategory implements ICategory
{

    /** @var int */
    private $id;

    /** @var Operation */
    private $operationType;

    /** @var string */
    private $name;

    public function __construct(int $id, Operation $operationType, string $name)
    {
        $this->id = $id;
        $this->operationType = $operationType;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperationType(): Operation
    {
        return $this->operationType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortcut(): string
    {
        return mb_substr($this->name, 0, 5, 'UTF-8');
    }

}
