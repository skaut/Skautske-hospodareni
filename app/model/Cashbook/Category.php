<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Doctrine\Common\Collections\ArrayCollection;

class Category implements ICategory
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
    private $deleted = false;

    /**
     * @param Category\ObjectType[] $types
     */
    public function __construct(
        int $id,
        string $name,
        string $shortcut,
        Operation $operationType,
        array $types,
        int $priority
    ) {
        $this->id            = $id;
        $this->name          = $name;
        $this->shortcut      = $shortcut;
        $this->operationType = $operationType;
        $this->types         = new ArrayCollection($types);
        $this->priority      = $priority;
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
        return $this->operationType->equalsValue(Operation::INCOME);
    }

    public function supportsType(ObjectType $type) : bool
    {
        return $this->types->exists(
            function ($_, Category\ObjectType $categoryType) use ($type) : bool {
                return $categoryType->getType()->equals($type);
            }
        );
    }

    public function isDeleted() : bool
    {
        return $this->deleted;
    }
}
