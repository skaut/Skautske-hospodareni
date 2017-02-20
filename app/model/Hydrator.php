<?php

declare(strict_types=1);

namespace Model;

use Dibi\DateTime;

/**
 * Creates domain objects from PHP array and vice versa. (ORM mapping)
 */
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
    public function __construct(string $className, array $properties)
    {
        $this->reflection = new \ReflectionClass($className);
        foreach ($properties as $propertyName) {
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
        foreach ($properties as $name => $value) {
            if ($value instanceof DateTime) {
                $timezone = $value->getTimezone();
                $value = \DateTimeImmutable::createFromFormat('U', (string)$value->getTimestamp());
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
    public function toArray($object): array
    {
        $row = [];
        foreach ($this->properties as $name => $property) {
            $value = $property->getValue($object);
            if ($value instanceof \DateTimeImmutable) {
                $timezone = $value->getTimezone();
                $value = DateTime::createFromFormat('U', (string)$value->getTimestamp());
                $value->setTimezone($timezone);
            }
            $row[$name] = $value;
        }
        return $row;
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    public function setProperty($object, string $property, $value): void
    {
        $this->properties[$property]->setValue($object, $value);
    }

}
