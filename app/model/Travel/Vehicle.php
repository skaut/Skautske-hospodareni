<?php

namespace Model\Travel;

use Nette;

class Vehicle extends Nette\Object
{

	/** @var int */
	private $id;

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
	 * @param int $id
	 * @param int $unitId
	 * @param string $registration
	 * @param float $consumption
	 */
	public function __construct($id, $unitId, $registration, $consumption)
	{
		$this->id = $id;
		$this->unitId = $unitId;
		$this->registration = $registration;
		$this->consumption = $consumption;
	}

	/**
	 * @param string $note
	 */
	public function setNote($note)
	{
		$this->note = $note;
	}

	/**
	 * @return int
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

}
