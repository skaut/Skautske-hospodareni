<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_chitsCategory")
 */
class Category implements ICategory
{
    public const EVENT_PARTICIPANTS_INCOME_CATEGORY_ID = 11;

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", name="label")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="short")
     */
    private $shortcut;

    /**
     * @var Operation
     * @ORM\Column(type="string_enum", name="type")
     * @EnumAnnotation(class=Operation::class)
     */
    private $operationType;

    /**
     * @var Category\ObjectType[]|ArrayCollection
     * @ORM\OneToMany(targetEntity=Category\ObjectType::class, mappedBy="category")
     */
    private $types;

    /**
     * @var int
     * @ORM\Column(type="integer", name="orderby")
     */
    private $priority;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $deleted = false;

    public function __construct()
    {
        $this->types = new ArrayCollection();
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
