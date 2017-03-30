<?php

namespace Model\Travel;

use Nette;

class Vehicle extends Nette\Object
{

    /** @var int|NULL */
    private $id;

    /** @var string */
    private $type;

    /** @var int */
    private $unitId;

    /** @var string */
    private $registration;

    /** @var float */
    private $consumption;

    /** @var string|NULL */
    private $note;

    /** @var bool */
    private $archived = FALSE;

    /**
     * Vehicle constructor.
     * @param string $type
     * @param int $unitId
     * @param string $registration
     * @param float $consumption
     */
    public function __construct($type, $unitId, $registration, $consumption)
    {
        $this->type = $type;
        $this->unitId = $unitId;
        $this->registration = $registration;
        $this->consumption = $consumption;
    }

    public function archive(): void
    {
        $this->archived = TRUE;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int|NULL
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUnitId()
    {
        return $this->unitId;
    }

    /**
     * @return string
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * @return float
     */
    public function getConsumption()
    {
        return $this->consumption;
    }

    /**
     * @return NULL|string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return boolean
     */
    public function isArchived()
    {
        return $this->archived;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        $label = $this->type . ' (' . $this->registration . ')';
        if ($this->archived) {
            $label .= ' [archivované]';
        }

        return $label;
    }

}
