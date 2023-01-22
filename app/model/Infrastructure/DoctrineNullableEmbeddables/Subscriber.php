<?php

declare(strict_types=1);

namespace Model\Infrastructure\DoctrineNullableEmbeddables;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionProperty;

use function strpos;

class Subscriber implements EventSubscriber
{
    public function __construct(private Reader $reader)
    {
    }

    /** @return string[] */
    public function getSubscribedEvents(): array
    {
        return ['postLoad'];
    }

    private function clearEmbeddablesIfNecessary(mixed $object, EntityManagerInterface $entityManager): void
    {
        $metadata = $entityManager->getClassMetadata($object::class);

        foreach ($metadata->embeddedClasses as $fieldName => $embeddable) {
            if (strpos($fieldName, '.') !== false) {
                continue;
            }

            $field = $metadata->getReflectionProperty($fieldName);
            $value = $field->getValue($object);

            if ($value === null) {
                continue;
            }

            if (! $this->hasNullableAnnotation($field)) {
                continue;
            }

            $this->clearEmbeddablesIfNecessary(
                $value,
                $entityManager,
            );

            if (! $this->isEmpty($value, $entityManager->getClassMetadata($embeddable['class']))) {
                continue;
            }

            $field->setValue($object, null);
        }
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        $this->clearEmbeddablesIfNecessary(
            $object,
            $args->getEntityManager(),
        );
    }

    private function isEmpty(mixed $object, ClassMetadata $metadata): bool
    {
        foreach ($metadata->getFieldNames() as $fieldName) {
            if ($metadata->getFieldValue($object, $fieldName) !== null) {
                return false;
            }
        }

        return true;
    }

    private function hasNullableAnnotation(ReflectionProperty $property): bool
    {
        return $this->reader->getPropertyAnnotation($property, Nullable::class) !== null;
    }
}
