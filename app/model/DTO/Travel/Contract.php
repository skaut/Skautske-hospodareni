<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Cake\Chronos\ChronosDate;
use Model\Travel\Contract\Passenger;
use Nette\SmartObject;

/**
 * @property-read int                       $id
 * @property-read Passenger                 $passenger
 * @property-read int                       $unitId
 * @property-read string                    $unitRepresentative
 * @property-read ChronosDate|NULL $since
 * @property-read ChronosDate|NULL $until
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
        private ChronosDate|null $since = null,
        private ChronosDate|null $until = null,
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

    public function getSince(): ChronosDate|null
    {
        return $this->since;
    }

    public function getUntil(): ChronosDate|null
    {
        return $this->until;
    }

    public function getTemplateVersion(): int
    {
        return $this->templateVersion;
    }
}
