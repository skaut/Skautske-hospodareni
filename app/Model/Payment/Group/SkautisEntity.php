<?php

declare(strict_types=1);

namespace App\Model\Payment\Group;

use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEducationId;
use App\Model\Event\SkautisEventId;
use App\Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Embeddable() */
final class SkautisEntity
{
    /** @ORM\Column(type="integer", nullable=true, name="sisId", options={"comment": "ID entity ve skautisu"}) */
    private int $id;

    /**
     * @ORM\Column(type="string_enum", nullable=true, name="groupType", length=20, options={"comment":"typ entity"})
     *
     * @Enum(class=Type::class)
     * @Nullable()
     * @var Type
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    private $type;

    public function __construct(int $id, Type $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public static function fromCampId(SkautisCampId $campId): self
    {
        return new self($campId->toInt(), Type::CAMP());
    }

    public static function fromEventId(SkautisEventId $eventId): self
    {
        return new self($eventId->toInt(), Type::EVENT());
    }

    public static function fromEducationId(SkautisEducationId $educationId): self
    {
        return new self($educationId->toInt(), Type::EDUCATION());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
