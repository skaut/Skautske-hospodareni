<?php

declare(strict_types=1);

namespace Model\Logger;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use Model\Logger\Log\Type;

/**
 * @ORM\Entity()
 * @ORM\Table(name="log")
 */
class LogEntry
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="integer", name="unitId")
     */
    private $unitId;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $date;

    /**
     * @var int
     * @ORM\Column(type="integer", name="userId")
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @var Type
     * @ORM\Column(type="string_enum", name="type")
     * @Enum(class=Type::class)
     */
    private $type;

    /**
     * @var int|NULL
     * @ORM\Column(type="integer", nullable=true, name="typeId")
     */
    private $typeId;

    public function __construct(
        int $unitId,
        int $userId,
        string $desc,
        Type $type,
        ?int $typeId,
        \DateTimeImmutable $at
    ) {
        $this->unitId      = $unitId;
        $this->date        = $at;
        $this->userId      = $userId;
        $this->description = $desc;
        $this->type        = $type;
        $this->typeId      = $typeId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getDate() : \DateTimeImmutable
    {
        return $this->date;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getType() : Type
    {
        return $this->type;
    }

    public function getTypeId() : ?int
    {
        return $this->typeId;
    }
}
