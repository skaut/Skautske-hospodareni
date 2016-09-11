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

	/** @var int */
	private $commandsCount;

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
	 * @param int $commandsCount
	 */
	public function __construct($type, $unitId, $registration, $consumption, $commandsCount)
	{
		$this->type = $type;
		$this->unitId = $unitId;
		$this->registration = $registration;
		$this->consumption = $consumption;
		$this->commandsCount = $commandsCount;
	}

	public function archive()
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
	 * @return int
	 */
	public function getCommandsCount()
	{
		return $this->commandsCount;
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

	public function getLabel()
	{
		return $this->type . ' ('. $this->registration . ')';
	}

}
