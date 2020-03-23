<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use Cake\Chronos\Date;
use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Travel\Type;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 *
 * @property-read DateTimeImmutable $date
 * @property-read Type $transportType
 * @property-read string $startPlace
 * @property-read string $endPlace
 */
class TravelDetails
{
    use SmartObject;

    /**
     * @ORM\Column(type="chronos_date", name="start_date")
     *
     * @var Date
     */
    private $date;

    /**
     * @ORM\Column(type="string_enum", name="type")
     *
     * @Enum(class=Type::class)
     * @var Type
     */
    private $transportType;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    private $startPlace;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    private $endPlace;

    public function __construct(Date $date, Type $transportType, string $startPlace, string $endPlace)
    {
        $this->date          = $date;
        $this->transportType = $transportType;
        $this->startPlace    = $startPlace;
        $this->endPlace      = $endPlace;
    }

    public function getDate() : Date
    {
        return $this->date;
    }

    public function getTransportType() : Type
    {
        return $this->transportType;
    }

    public function getStartPlace() : string
    {
        return $this->startPlace;
    }

    public function getEndPlace() : string
    {
        return $this->endPlace;
    }
}
