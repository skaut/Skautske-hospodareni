<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Category;

use App\Model\Cashbook\Category;
use App\Model\Cashbook\ObjectType as ObjectTypeEnum;
use Consistence\Doctrine\Enum\EnumAnnotation;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ac_chitsCategory_object",
 *     indexes={@ORM\Index(name="type", columns={"type"})}
 * )
 */
class ObjectType
{
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="types")
     * @ORM\JoinColumn(nullable=false)
     */
    private Category $category;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string_enum", length=20)
     *
     * @var ObjectTypeEnum
     * @EnumAnnotation(class=ObjectTypeEnum::class)
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
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
