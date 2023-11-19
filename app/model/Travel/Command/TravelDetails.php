<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use Cake\Chronos\ChronosDate;
use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Travel\TransportType;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 *
 * @property-read Date $date
 * @property-read TransportType $transportType
 * @property-read string $startPlace
 * @property-read string $endPlace
 */
class TravelDetails
{
    use SmartObject;

    /** @ORM\Column(type="chronos_date", name="start_date") */
    private ChronosDate $date;

    /**
     * @ORM\Column(type="string_enum")
     *
     * @Enum(class=TransportType::class)
     * @var TransportType
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    private $transportType;

    /** @ORM\Column(type="string", length=64) */
    private string $startPlace;

    /** @ORM\Column(type="string", length=64) */
    private string $endPlace;

    public function __construct(ChronosDate $date, TransportType $transportType, string $startPlace, string $endPlace)
    {
        $this->date          = $date;
        $this->transportType = $transportType;
        $this->startPlace    = $startPlace;
        $this->endPlace      = $endPlace;
    }

    public function getDate(): ChronosDate
    {
        return $this->date;
    }

    public function getTransportType(): TransportType
    {
        return $this->transportType;
    }

    public function getStartPlace(): string
    {
        return $this->startPlace;
    }

    public function getEndPlace(): string
    {
        return $this->endPlace;
    }
}
