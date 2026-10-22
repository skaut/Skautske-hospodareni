<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Category;

use App\Model\Cashbook\Category;
use App\Model\Cashbook\ObjectType as ObjectTypeEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ac_chitsCategory_object')]
#[ORM\Index(name: 'type', columns: ['type'])]
class ObjectType
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'types')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    /**
     * @var ObjectTypeEnum
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    #[ORM\Id]
    #[ORM\Column(type: 'cashbook_object_type', length: 20)]
    private $type;

    public function __construct(Category $category, ObjectTypeEnum $value)
    {
        $this->category = $category;
        $this->type = $value;
    }

    public function getType(): ObjectTypeEnum
    {
        return $this->type;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
