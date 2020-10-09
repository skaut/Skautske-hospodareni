<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Cake\Chronos\Date;
use DateTimeImmutable;
use Model\Travel\Contract\Passenger;
use Nette\SmartObject;

/**
 * @property-read int                       $id
 * @property-read Passenger                 $passenger
 * @property-read int                       $unitId
 * @property-read string                    $unitRepresentative
 * @property-read DateTimeImmutable|NULL $since
 * @property-read DateTimeImmutable|NULL $until
 * @property-read int                       $templateVersion
 */
class Contract
{
    use SmartObject;

    private int $id;

    private Passenger $passenger;

    private int $unitId;

    private string $unitRepresentative;

    private ?Date $since;

    private ?Date $until;

    private int $templateVersion;

    public function __construct(
        int $id,
        Passenger $passenger,
        int $unitId,
        string $unitRepresentative,
        ?Date $since,
        ?Date $until,
        int $templateVersion
    ) {
        $this->id                 = $id;
        $this->passenger          = $passenger;
        $this->unitId             = $unitId;
        $this->unitRepresentative = $unitRepresentative;
        $this->since              = $since;
        $this->until              = $until;
        $this->templateVersion    = $templateVersion;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPassenger() : Passenger
    {
        return $this->passenger;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getUnitRepresentative() : string
    {
        return $this->unitRepresentative;
    }

    public function getSince() : ?Date
    {
        return $this->since;
    }

    public function getUntil() : ?Date
    {
        return $this->until;
    }

    public function getTemplateVersion() : int
    {
        return $this->templateVersion;
    }
}
