<?php

namespace Model\Bank\Http;

class Response
{

	/** @var int */
	private $code;

	/** @var string */
	private $body;

	/** @var bool */
	private $timeout;

	/**
	 * Response constructor.
	 * @param int $code
	 * @param string $body
	 * @param bool $timeout
	 */
	public function __construct($code, $body, $timeout)
	{
		$this->code = $code;
		$this->body = $body;
		$this->timeout = $timeout;
	}

	/**
	 * @return int
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return boolean
	 */
	public function isTimeout()
	{
		return $this->timeout;
	}

}