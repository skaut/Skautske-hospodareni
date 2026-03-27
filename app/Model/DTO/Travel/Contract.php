<?php

declare(strict_types=1);

namespace App\Model\DTO\Travel;

use App\Model\Travel\Contract\Passenger;
use Cake\Chronos\ChronosDate;
use Nette\SmartObject;

/**
 * @property int              $id
 * @property Passenger        $passenger
 * @property int              $unitId
 * @property string           $unitRepresentative
 * @property ChronosDate|null $since
 * @property ChronosDate|null $until
 * @property int              $templateVersion
 */
class Contract
{
    use SmartObject;

    public function __construct(
        private int $id,
        private Passenger $passenger,
        private int $unitId,
        private string $unitRepresentative,
        private ?ChronosDate $since,
        private ?ChronosDate $until,
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

    public function getSince(): ?ChronosDate
    {
        return $this->since;
    }

    public function getUntil(): ?ChronosDate
    {
        return $this->until;
    }

    public function getTemplateVersion(): int
    {
        return $this->templateVersion;
    }
}
