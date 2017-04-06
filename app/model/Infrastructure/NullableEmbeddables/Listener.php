<?php

namespace Model\Infrastructure\NullableEmbeddables;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Kdyby\Events\Subscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Model\Payment\Payment;
use ReflectionClass;

class Listener implements Subscriber
{

    /** @var ObjectManager */
    private $manager;

    /** @var string[] */
    private $embeddablesTree = [];

    /** @var ReflectionClass[] */
    private $reflections = [];

    /** @var Reader */
    private $reader;

    public function getSubscribedEvents()
    {
        return ['postLoad'];
    }

    public function __construct(ObjectManager $manager, Reader $reader)
    {
        $this->manager = $manager;
        $this->reader = $reader;
    }

    private function getNullableEmbeddables(ClassMetadata $metadata, $prefix = NULL)
    {
        if (!isset($this->embeddablesTree[$metadata->getName()])) {
            $nullables = [];
            foreach ($metadata->embeddedClasses as $field => $embeddable) {
                $prefixedField = $prefix !== NULL ? $prefix . '.' . $field : $field;

                $nullables = array_merge(
                    $nullables,
                    $this->getNullableEmbeddables(
                        $this->manager->getClassMetadata($embeddable['class']),
                        $prefixedField
                    )
                );

                $annotation = $this->reader->getPropertyAnnotation(
                    $metadata->getReflectionProperty($field),
                    NullableAnnotation::class
                    );

                if ($annotation !== NULL) {
                    $nullables[] = $prefixedField;
                }
            }
            $this->embeddablesTree[$metadata->getName()] = $nullables;
        }

        return $this->embeddablesTree[$metadata->getName()];
    }

    private function getReflection(string $class): ReflectionClass
    {
        if (!isset($this->reflections[$class])) {
            $this->reflections[$class] = new ReflectionClass($class);
        }
        return $this->reflections[$class];
    }

    private function clearEmbeddableIfNecessary($object, string $field): void
    {
        if (!$object || $object instanceof Proxy) {
            return;
        }

        $nested = strpos($field, '.');

        $reflection = $this->getReflection(get_class($object));

        $property = $reflection->getProperty($nested === FALSE ? $field : substr($field, 0, $nested));
        $property->setAccessible(TRUE);

        if ($nested === FALSE) {
            if ($this->isEmpty($property->getValue($object))) {
                $property->setValue($object, NULL);
            }
        } else {
            $this->clearEmbeddableIfNecessary($property->getValue($object), substr($field, $nested + 1));
        }
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        $className = get_class($object);
        $metadata = $args->getObjectManager()->getClassMetadata($className);

        foreach ($this->getNullableEmbeddables($metadata) as $embeddable) {
            $this->clearEmbeddableIfNecessary($object, $embeddable);
        }
    }

    private function isEmpty($object): bool
    {
        if (empty($object)) {
            return TRUE;
        } elseif (is_numeric($object)) {
            return FALSE;
        } elseif (is_string($object)) {
            return !strlen(trim($object));
        }

        // It's an object or array!
        foreach ((array)$object as $element) {
            if (!$this->isEmpty($element)) {
                return FALSE;
            }
        }

        return TRUE;
    }

}
