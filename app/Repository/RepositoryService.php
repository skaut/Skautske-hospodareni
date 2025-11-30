<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Illuminate\Support\Collection as IlluminateCollection;

class RepositoryService
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // ukradeno z fasady

    /**
     * @param class-string $class
     */
    public function castToEntity(string $class): callable
    {
        return $this->castNotBlank(function (mixed $value) use ($class): object {
            /** @var object|null $entity */
            $entity = $this->entityManager->getRepository($class)->find($value);

            if ($entity === null) {
                throw new NoResultException();
            }

            return $entity;
        });
    }

    public function castNotBlank(callable $callback): callable
    {
        return function (mixed $value, IlluminateCollection $context) use ($callback): mixed {
            if ($value !== null && $value !== '') {
                return $callback($value, $context);
            }

            return $value;
        };
    }
}
