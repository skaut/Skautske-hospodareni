<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Model\Cashbook\Operation;

class Category
{
    /** @var int */
    private $id;

    /** @var Operation */
    private $operationType;

    public function __construct(int $id, Operation $operationType)
    {
        $this->id            = $id;
        $this->operationType = $operationType;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getOperationType() : Operation
    {
        return $this->operationType;
    }
}
