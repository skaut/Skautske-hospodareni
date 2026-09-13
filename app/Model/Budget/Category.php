<?php

declare(strict_types=1);

namespace App\Model\Budget\Unit;

use App\Model\Cashbook\Operation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ac_unit_budget_category')]
#[ORM\Index(name: 'unitId_year', columns: ['unit_id', 'year'])]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $unitId;

    #[ORM\Column(type: 'string', length: 64)]
    private string $label;

    /**
     * @var Operation
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    #[ORM\Column(type: 'cashbook_operation')]
    private $type;

    /**
     * @var Collection&iterable<self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist'])]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parentId', referencedColumnName: 'id')]
    private ?Category $parent = null;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $value;

    #[ORM\Column(type: 'smallint')]
    private int $year;

    public function __construct(int $unitId, string $label, Operation $type, ?Category $parent, float $value, int $year)
    {
        $this->unitId = $unitId;
        $this->label = $label;
        $this->type = $type;
        $this->children = new ArrayCollection();
        if ($parent !== null) {
            $this->parent = $parent;
            $this->parent->children->add($this);
        }

        $this->value = $value;
        $this->year = $year;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    /** @return Category[] */
    public function getChildren(): array
    {
        return $this->children->toArray();
    }
}
