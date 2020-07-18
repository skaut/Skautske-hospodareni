<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ac_chitsCategory",
 *     indexes={
 *          @ORM\Index(name="deleted", columns={"deleted"}),
 *          @ORM\Index(name="orderby", columns={"orderby"}),
 *     }
 * )
 * @ORM\Cache()
 */
class Category implements ICategory
{
    public const EVENT_PARTICIPANTS_INCOME_CATEGORY_ID = 11;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /** @ORM\Column(type="string", name="label", length=64) */
    private string $name;

    /** @ORM\Column(type="string", name="short", length=64, unique=true) */
    private string $shortcut;

    /**
     * @ORM\Column(type="string_enum", name="type")
     *
     * @EnumAnnotation(class=Operation::class)
     */
    private Operation $operationType;

    /**
     * @ORM\OneToMany(targetEntity=Category\ObjectType::class, mappedBy="category")
     *
     * @var Category\ObjectType[]|ArrayCollection
     */
    private $types;

    /** @ORM\Column(type="boolean") */
    private bool $virtual;

    /** @ORM\Column(type="smallint", name="orderby", options={"unsigned"=true}) */
    private int $priority;

    /** @ORM\Column(type="boolean") */
    private bool $deleted = false;

    /**
     * @param Category\ObjectType[] $types
     */
    public function __construct(
        int $id,
        string $name,
        string $shortcut,
        Operation $operationType,
        array $types,
        bool $virtual,
        int $priority
    ) {
        $this->id            = $id;
        $this->name          = $name;
        $this->shortcut      = $shortcut;
        $this->operationType = $operationType;
        $this->types         = new ArrayCollection($types);
        $this->virtual       = $virtual;
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
            function ($_x, Category\ObjectType $categoryType) use ($type) : bool {
                return $categoryType->getType()->equals($type);
            }
        );
    }

    public function isVirtual() : bool
    {
        return $this->virtual;
    }

    public function isDeleted() : bool
    {
        return $this->deleted;
    }
}
