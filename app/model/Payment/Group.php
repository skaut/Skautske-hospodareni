<?php

namespace Model\Payment;

class Group
{

	/** @var int */
	private $id;

	/** @var string */
	private $type;

	/** @var int */
	private $unitId;

	/** @var int */
	private $eventId;

	/** @var string */
	private $name;

	/** @var float */
	private $defaultAmount;

	/** @var \DateTimeImmutable */
	private $dueDate;

	/** @var int */
	private $constantSymbol;

	/** @var string */
	private $state = self::STATE_OPEN;

	/** @var \DateTimeImmutable */
	private $createdAt;

	const STATE_OPEN = 'open';

	/**
	 * Group constructor.
	 * @param int $id
	 * @param string $type
	 * @param int $unitId
	 * @param int $eventId
	 * @param string $name
	 * @param float $defaultAmount
	 * @param \DateTimeImmutable $dueDate
	 * @param int $constantSymbol
	 * @param \DateTimeImmutable $createdAt
	 */
	public function __construct(
		$id,
		$type,
		$unitId,
		$eventId,
		$name,
		$defaultAmount,
		\DateTimeImmutable $dueDate,
		$constantSymbol,
		\DateTimeImmutable $createdAt)
	{
		$this->id = $id;
		$this->type = $type;
		$this->unitId = $unitId;
		$this->eventId = $eventId;
		$this->name = $name;
		$this->defaultAmount = $defaultAmount;
		$this->dueDate = $dueDate;
		$this->constantSymbol = $constantSymbol;
		$this->createdAt = $createdAt;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getUnitId()
	{
		return $this->unitId;
	}

	/**
	 * @return int
	 */
	public function getEventId()
	{
		return $this->eventId;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return float
	 */
	public function getDefaultAmount()
	{
		return $this->defaultAmount;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	public function getDueDate()
	{
		return $this->dueDate;
	}

	/**
	 * @return int
	 */
	public function getConstantSymbol()
	{
		return $this->constantSymbol;
	}

	/**
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}

}
