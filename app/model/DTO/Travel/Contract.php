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

    public function __construct(
        private int $id,
        private Passenger $passenger,
        private int $unitId,
        private string $unitRepresentative,
        private Date|null $since = null,
        private Date|null $until = null,
        private int $templateVersion,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPassenger(): Passenger
    {
        return $this->passenger;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUnitRepresentative(): string
    {
        return $this->unitRepresentative;
    }

    public function getSince(): Date|null
    {
        return $this->since;
    }

    public function getUntil(): Date|null
    {
        return $this->until;
    }

    public function getTemplateVersion(): int
    {
        return $this->templateVersion;
    }
}
