<?php

namespace Model;

class Hydrator
{

	/** @var string */
	private $className;

	/** @var \ReflectionProperty[] */
	private $properties;

	/**
	 * Hydrator constructor.
	 * @param string $className
	 * @param string[] $properties
	 */
	public function __construct($className, array $properties)
	{
		$this->className = $className;
		$reflection = new \ReflectionClass($className);
		foreach($properties as $propertyName) {
			$property = $reflection->getProperty($propertyName);
			$property->setAccessible(TRUE);
			$this->properties[$propertyName] = $properties;
		}
	}

	/**
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($object, $name, $value)
	{
		$this->properties[$name]->setValue($object, $value);
	}

	/**
	 * @param object $object
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($object, $name)
	{
		return $this->properties[$name]->getValue($object);
	}

}
