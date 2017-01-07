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

	/**
	 * @param object $object
	 * @return array
	 */
	public function toArray($object)
	{
		$row = [];
		foreach($this->properties as $name => $property) {
			$value = $property->getValue($object);
			if($value instanceof \DateTimeImmutable) {
				$timezone = $value->getTimezone();
				$value = DateTime::createFromFormat('U', $value->getTimestamp());
				$value->setTimezone($timezone);
			}
			$row[$name] = $value;
		}
		return $row;
	}

	/**
	 * @param $object
	 * @param $property
	 * @param $value
	 */
	public function setProperty($object, $property, $value)
	{
		$this->properties[$property]->setValue($object, $value);
	}

}
