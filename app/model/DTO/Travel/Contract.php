<?php


namespace Model\DTO\Travel;

use Nette\SmartObject;


/**
 * @property-read int $id
 * @property-read string $driverName
 * @property-read string $unitRepresentative
 * @property-read \DateTimeImmutable|NULL $since
 * @property-read \DateTimeImmutable|NULL $until
 */
class Contract
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $driverName;

    /** @var string */
    private $unitRepresentative;

    /** @var \DateTimeImmutable|NULL */
    private $since;

    /** @var \DateTimeImmutable|NULL */
    private $until;


    public function __construct(
        int $id,
        string $driverName,
        string $unitRepresentative,
        ?\DateTimeImmutable $since,
        ?\DateTimeImmutable $until
    )
    {
        $this->id = $id;
        $this->driverName = $driverName;
        $this->unitRepresentative = $unitRepresentative;
        $this->since = $since;
        $this->until = $until;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function getUnitRepresentative(): string
    {
        return $this->unitRepresentative;
    }

    public function getSince()
    {
        return $this->since;
    }

    public function getUntil()
    {
        return $this->until;
    }

}
