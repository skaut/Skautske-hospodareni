<?php

namespace Model\Cashbook;

use Doctrine\Common\Collections\ArrayCollection;

class Category
{

    public const EVENT_PARTICIPANTS_INCOME_CATEGORY_ID = 11;

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $shortcut;

    /** @var Operation */
    private $operationType;

    /** @var Category\ObjectType[]|ArrayCollection */
    private $types;

    /** @var int */
    private $priority;

    /** @var bool */
    private $deleted = FALSE;

    public function __construct()
    {
        $this->types = new ArrayCollection();
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
        return $this->operationType->equalsValue(Operation::INCOME);
    }

}
