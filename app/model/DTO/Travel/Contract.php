<?php


namespace Model\DTO\Travel;

use Nette\SmartObject;


/**
 * @property-read int                       $id
 * @property-read string                    $driverName
 * @property-read string                    $driverContact
 * @property-read string                    $driverAddress
 * @property-read \DateTimeImmutable        $driverBirthday
 * @property-read int                       $unitId
 * @property-read string                    $unitRepresentative
 * @property-read \DateTimeImmutable|NULL   $since
 * @property-read \DateTimeImmutable|NULL   $until
 * @property-read int                       $templateVersion
 */
class Contract
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $driverName;

    /** @var string */
    private $driverContact;

    /** @var string */
    private $driverAddress;

    /** @var \DateTimeImmutable */
    private $driverBirthday;

    /** @var int */
    private $unitId;

    /** @var string */
    private $unitRepresentative;

    /** @var \DateTimeImmutable|NULL */
    private $since;

    /** @var \DateTimeImmutable|NULL */
    private $until;

    /** @var int */
    private $templateVersion;

    public function __construct(
        int $id,
        string $driverName,
        string $driverContact,
        string $driverAddress,
        \DateTimeImmutable $driverBirthday,
        int $unitId,
        string $unitRepresentative,
        ?\DateTimeImmutable $since,
        ?\DateTimeImmutable $until,
        int $templateVersion
    )
    {
        $this->id = $id;
        $this->driverName = $driverName;
        $this->driverContact = $driverContact;
        $this->driverAddress = $driverAddress;
        $this->driverBirthday = $driverBirthday;
        $this->unitId = $unitId;
        $this->unitRepresentative = $unitRepresentative;
        $this->since = $since;
        $this->until = $until;
        $this->templateVersion = $templateVersion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function getDriverContact(): string
    {
        return $this->driverContact;
    }

    public function getDriverAddress(): string
    {
        return $this->driverAddress;
    }

    public function getDriverBirthday(): \DateTimeImmutable
    {
        return $this->driverBirthday;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUnitRepresentative(): string
    {
        return $this->unitRepresentative;
    }

    public function getSince(): ?\DateTimeImmutable
    {
        return $this->since;
    }

    public function getUntil(): ?\DateTimeImmutable
    {
        return $this->until;
    }

    public function getTemplateVersion(): int
    {
        return $this->templateVersion;
    }

}
