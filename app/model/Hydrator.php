<?php

namespace Model;

use Dibi\DateTime;

class Hydrator
{

	/** @var \ReflectionClass */
	private $reflection;

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
		$this->reflection = new \ReflectionClass($className);
		foreach($properties as $propertyName) {
			$property = $this->reflection->getProperty($propertyName);
			$property->setAccessible(TRUE);
			$this->properties[$propertyName] = $property;
		}
	}

	/**
	 * @param array $properties
	 * @return mixed
	 */
	public function create(array $properties)
	{
		$object = $this->reflection->newInstanceWithoutConstructor();
		foreach($properties as $name => $value) {
			if($value instanceof DateTime) {
				$timezone = $value->getTimezone();
				$value = \DateTimeImmutable::createFromFormat('U', $value->getTimestamp());
				$value = $value->setTimezone($timezone);
			}
			$this->properties[$name]->setValue($object, $value);
		}
		return $object;
	}

}
