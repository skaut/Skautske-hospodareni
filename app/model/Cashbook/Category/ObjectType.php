<?php

declare(strict_types=1);

namespace Model\Cashbook\Category;

use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\ORM\Mapping as ORM;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType as ObjectTypeEnum;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ac_chitsCategory_object",
 *     indexes={@ORM\Index(name="objectTypeId", columns={"objectTypeId"})}
 * )
 */
class ObjectType
{
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(name="categoryId", nullable=false)
     */
    private Category $category;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string_enum", name="objectTypeId", length=20)
     *
     * @EnumAnnotation(class=ObjectTypeEnum::class)
     */
    private ObjectTypeEnum $type;

    public function __construct(Category $category, ObjectTypeEnum $value)
    {
        $this->category = $category;
        $this->type     = $value;
    }

    public function getType() : ObjectTypeEnum
    {
        return $this->type;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }
}
