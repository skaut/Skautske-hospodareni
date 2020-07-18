<?php

declare(strict_types=1);

namespace Model\Budget\Unit;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Operation;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ac_unit_budget_category",
 *     indexes={@ORM\Index(name="objectId_year", columns={"unit_id", "year"})}
 * )
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /** @ORM\Column(type="integer", nullable=true) */
    private int $unitId;

    /** @ORM\Column(type="string", length=64) */
    private string $label;

    /**
     * @ORM\Column(type="string_enum")
     *
     * @EnumAnnotation(class=\Model\Cashbook\Operation::class)
     */
    private Operation $type;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent",cascade={"persist"})
     *
     * @var ArrayCollection|Category[]
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id")
     */
    private Category $parent;

    /** @ORM\Column(type="float", options={"unsigned"=true, "default"=0}) */
    private float $value;

    /** @ORM\Column(type="smallint", options={"unsigned"=true}) */
    private int $year;

    public function __construct(int $unitId, string $label, Operation $type, ?Category $parent, float $value, int $year)
    {
        $this->unitId   = $unitId;
        $this->label    = $label;
        $this->type     = $type;
        $this->children = new ArrayCollection();
        if ($parent !== null) {
            $this->parent = $parent;
            $this->parent->children->add($this);
        }

        $this->value = $value;
        $this->year  = $year;
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
        return $this->children->toArray();
    }
}
