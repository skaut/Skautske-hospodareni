<?php


namespace Model\Infrastructure;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;

class YamlReader implements Reader
{

    /** @var Configuration */
    private $configuration;

    /** @var YamlDriver[] */
    private $drivers;

    /** @var array */
    private $aliases;

    /** @var array */
    private $propertyAnnotations = [];

    /** @var string[] */
    private $classes = [];

    public function __construct(EntityManager $em, array $aliases = [])
    {
        $this->configuration = $em->getConfiguration();
    }

    /**
     * @return YamlDriver[]
     */
    private function getDrivers(): array
    {
        if($this->drivers !== NULL) {
            return $this->drivers;
        }

        $chain = $this->configuration->getMetadataDriverImpl();
        $drivers = $chain instanceof MappingDriverChain ? $chain->getDrivers() : [$chain];

        $this->drivers = $drivers;

        foreach ($drivers as $driver) {
            if ($driver instanceof YamlDriver) {
                $this->drivers[] = $driver;
            }
        }

        return $this->drivers;
    }

    private function loadClass(string $class): void
    {
        if(in_array($class, $this->classes)) {
            return;
        }

        foreach($this->getDrivers() as $driver) {
            if(!in_array($class, $driver->getAllClassNames(), TRUE)) {
                continue;
            }
            $classElement = $driver->getElement($class);

            $fields = $classElement['fields'] ?? [];
            foreach($fields as $propertyName => $field) {
                if(!isset($field['annotations']) || !is_array($field['annotations'])) {
                    continue;
                }

                $propertyAnnotations = [];
                foreach($field['annotations'] as $annotationName => $parameters) {
                    $annotationName = $this->aliases[$annotationName] ?? $annotationName;
                    $propertyAnnotations[$annotationName] = (object)$parameters;
                }

                $this->propertyAnnotations[$class . '::' . $propertyName] = $propertyAnnotations;
            }
        }

        $this->classes[] = $class;
    }

    public function getClassAnnotations(\ReflectionClass $class)
    {
        return NULL;
    }

    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return NULL;
    }

    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return NULL;
    }

    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return NULL;
    }

    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return NULL;
    }

    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        $class = $property->getDeclaringClass()->getName();
        $this->loadClass($class);

        return $this->propertyAnnotations[$class.'::'.$property->getName()][$annotationName] ?? NULL;
    }

}
