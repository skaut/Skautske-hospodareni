<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use Fmasa\DoctrineNullableEmbeddables\Annotations\Nullable;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;

/**
 * @ORM\Embeddable()
 */
final class SkautisEntity
{
    /**
     * @ORM\Column(type="integer", nullable=true, name="sisId", options={"comment": "ID entity ve skautisu"})
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string_enum", nullable=true, name="groupType", length=20, options={"comment":"typ entity"})
     *
     * @var Type
     * @Enum(class=Type::class)
     * @Nullable()
     */
    private $type;

    public function __construct(int $id, Type $type)
    {
        $this->id   = $id;
        $this->type = $type;
    }

    public static function fromCampId(SkautisCampId $campId) : self
    {
        return new self($campId->toInt(), Type::get(Type::CAMP));
    }

    public static function fromEventId(SkautisEventId $eventId) : self
    {
        return new self($eventId->toInt(), Type::get(Type::EVENT));
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
