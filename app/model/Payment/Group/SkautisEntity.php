<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use Fmasa\DoctrineNullableEmbeddables\Annotations\Nullable;

/**
 * @ORM\Embeddable()
 */
final class SkautisEntity
{
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true, name="sisId", options={"comment": "ID entity ve skautisu"})
     */
    private $id;

    /**
     * @var Type
     * @ORM\Column(type="string_enum", nullable=true, name="groupType", length=20, options={"comment":"typ entity"})
     * @Enum(class=Type::class)
     * @Nullable()
     */
    private $type;

    public function __construct(int $id, Type $type)
    {
        $this->id   = $id;
        $this->type = $type;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getType() : Type
    {
        return $this->type;
    }
}
