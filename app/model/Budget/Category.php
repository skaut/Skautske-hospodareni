<?php

declare(strict_types=1);

namespace Model\Budget\Unit;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Operation;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ac_unit_budget_category")
 */
class Category
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $unitId;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $label;

    /**
     * @var Operation
     * @ORM\Column(type="string_enum", options={"default"="out"})
     * @EnumAnnotation(class=\Model\Cashbook\Operation::class)
     */
    private $type;

    /**
     * @var ArrayCollection|Category[]
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent",cascade={"persist"})
     */
    private $children;

    /**
     * @var Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var float
     * @ORM\Column(type="float", options={"unsigned"=true, "default"=0})
     */
    private $value;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $year;

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
