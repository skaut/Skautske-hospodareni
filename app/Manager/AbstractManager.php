<?php

declare(strict_types=1);

namespace Manager;

use DateTimeInterface;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\PessimisticLockException;
use Entity\AbstractEntity;
use Entity\AbstractIdEntity;
use Entity\SingleIdentifierEntityInterface;
use Extension\Doctrine\Entity\SoftDeletableEntityInterface;
use Illuminate\Support\Collection as IlluminateCollection;
use InvalidArgumentException;
use Repository\AbstractRepository;
use RuntimeException;

use function array_fill;
use function array_keys;
use function array_map;
use function array_values;
use function collect;
use function count;
use function implode;
use function ksort;
use function max;

abstract class AbstractManager
{
    protected EntityManagerInterface $em;
    // private ManagerRegistry $managerRegistry;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->setManagerRegistry($entityManager);
    }

    public function setManagerRegistry(EntityManagerInterface $entityManager): void
    {
        // $this->managerRegistry = $managerRegistry;
        // $em = $managerRegistry->getManagerForClass($this->getEntityClass());

        //        if (! $em instanceof EntityManagerInterface) {
        //            throw new LogicException('Could not find an entity manager for class '.static::class);
        //        }

        $this->em = $entityManager;
    }

    /** @return class-string */
    abstract public function getEntityClass(): string;

    /**
     * @param callable(EntityManagerInterface): T $callback
     *
     * @return T
     *
     * @template T
     */
    public function wrapInTransaction(callable $callback): mixed
    {
        return $this->em->wrapInTransaction($callback);
    }

    /**
     * @param Collection<int,E>                  $collection
     * @param iterable<array-key, R>             $rows
     * @param callable(R,array-key):E            $createCallback
     * @param callable(E,R,array-key):E          $editCallback
     * @param callable(E):void                   $deleteCallback
     * @param callable(R): (array-key|null)|null $rowIdentifierCallback
     * @param callable(E): (array-key|null)|null $entityIdentifierCallback
     *
     * @return Collection<int,E>
     *
     * @template E of AbstractIdEntity
     * @template R
     */
    public function updateMultiplierCollection(
        Collection $collection,
        iterable $rows,
        callable $createCallback,
        callable $editCallback,
        callable $deleteCallback,
        ?callable $rowIdentifierCallback = null,
        ?callable $entityIdentifierCallback = null,
        bool $createWhenMissingInCollection = false,
    ): Collection {
        $rowIdentifierCallback ??= fn (mixed $row) => $row['id'] ?? null;
        $entityIdentifierCallback ??= fn ($entity) => $entity->getId();

        /** @var ArrayCollection<array-key, E> $collectionByIdentifier */
        $collectionByIdentifier = new ArrayCollection();

        foreach ($collection as $value) {
            $collectionByIdentifier[$entityIdentifierCallback($value)] = $value;
        }

        foreach ($rows as $rowIndex => $row) {
            $identifier = $rowIdentifierCallback($row);

            if ($identifier !== null) {
                $entity = $collectionByIdentifier[$identifier] ?? null;
            } else {
                $entity = null;
            }

            if ($entity !== null) {
                $editCallback($entity, $row, $rowIndex);
                unset($collectionByIdentifier[$identifier]);
            } elseif ($identifier === null || $createWhenMissingInCollection) {
                $createCallback($row, $rowIndex);
            } else {
                throw new RuntimeException("Entity with identifier '{$identifier}' does not exist");
            }
        }

        // delete
        foreach ($collectionByIdentifier as $entity) {
            $deleteCallback($entity);
        }

        return $collection;
    }

    /**
     * @param Collection<TKey, TValue>            $collection
     * @param AbstractRepository<TValue>          $repository
     * @param array<array-key, int|string|TValue> $values
     *
     * @return Collection<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue of SingleIdentifierEntityInterface
     */
    public function updateCollectionById(object $parent, Collection $collection, AbstractRepository $repository, array $values, bool $indexedById = false): Collection
    {
        /** @var IlluminateCollection<array-key, TValue> $valueEntities */
        $valueEntities = collect($values)
            ->map(function (mixed $value) use ($repository): SingleIdentifierEntityInterface {
                if ($value instanceof SingleIdentifierEntityInterface) {
                    return $value;
                }

                return $repository->findOrFail($value);
            });

        foreach ($collection as $entity) {
            if ($valueEntities->contains($entity) !== false) {
                continue;
            }

            $collection->removeElement($entity);
        }

        foreach ($valueEntities as $entity) {
            if ($collection->contains($entity) !== false) {
                continue;
            }

            if ($indexedById === true) {
                $collection[$entity->getId()] = $entity;
            } else {
                $collection->add($entity);
            }
        }

        $this->saveEntity($parent);

        return $collection;
    }

    protected function saveEntity(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    protected function hardDeleteEntity(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    protected function deleteEntity(object $entity): void
    {
        if ($entity instanceof SoftDeletableEntityInterface) {
            $entity->setDeleted();
            $this->em->persist($entity);
        } else {
            $this->em->remove($entity);
        }

        $this->em->flush();
    }

    protected function initializeEntity(object $entity): void
    {
        $this->em->initializeObject($entity);
    }

    /** @phpstan-param LockMode::* $lockMode */
    public function refresh(object $entity, ?int $lockMode = null): void
    {
        $this->em->refresh($entity, $lockMode);
    }

    /**
     * @phpstan-param LockMode::* $lockMode
     *
     * @throws OptimisticLockException
     * @throws PessimisticLockException
     */
    public function lock(object $entity, int $lockMode, int|DateTimeInterface|null $lockVersion = null): void
    {
        $this->em->lock($entity, $lockMode, $lockVersion);
    }

    public function clearEntityManager(): void
    {
        $this->em->clear();
    }

    public function resetEntityManager(): void
    {
        throw new RuntimeException('Entity manager reset is not supported in this implementation.');
    }

    public function isEntityManagerOpen(): bool
    {
        return $this->em->isOpen();
    }

    /** @return bool whether the entity manager has been reset */
    public function clearOrResetEntityManager(): bool
    {
        if ($this->isEntityManagerOpen()) {
            $this->clearEntityManager();

            return false;
        }

        $this->resetEntityManager();

        return true;
    }

    protected function restoreDeletedEntity(object $entity): void
    {
        if (! ($entity instanceof SoftDeletableEntityInterface)) {
            return;
        }

        $entity->setDeleted(false);
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * @param array<string, mixed>[] $rows
     * @param string[]               $updateColumns
     *
     * @throws DbalException
     */
    public function bulkInsertUpdate(string $table, array $rows, array $updateColumns): int
    {
        if (empty($rows)) {
            return 0;
        }

        $query = null;
        $placeholders = null;
        $columns = null;
        $rowCount = count($rows);
        $params = [];

        for ($i = 0; $i < $rowCount; ++$i) {
            $row = $rows[$i];
            ksort($row);

            if ($i === 0) {
                $columns = array_keys($row);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $query = 'INSERT INTO '.$table.' ('.implode(', ', $columns).') VALUES ';
            } elseif (array_keys($row) !== $columns) {
                /** @var string[] $columns */
                throw new InvalidArgumentException("Row [{$i}] must contain exactly these columns: ".implode(', ', $columns));
            }

            $separator = $i === $rowCount - 1 ? '' : ',';
            $query .= "($placeholders){$separator}";
            $params = [
                ...$params,
                ...array_values($row),
            ];
        }

        if (! empty($updateColumns)) {
            $query .= ' ON DUPLICATE KEY UPDATE '.implode(', ', array_map(function ($columnName) {
                return "$columnName=VALUES($columnName)";
            }, $updateColumns));
        }

        return (int) $this->em
            ->getConnection()
            ->executeStatement($query, $params);
    }

    /** @param Collection<array-key, mixed> $collection */
    public function isCollectionInitialized(Collection $collection): bool
    {
        if ($collection instanceof AbstractLazyCollection) {
            return $collection->isInitialized();
        }

        return true;
    }

    /**
     * @param Collection<array-key, T> $collection
     * @param T                        $element
     *
     * @return bool true if element was actually added, false otherwise
     *
     * @template T
     */
    public function addElementIfInitialized(Collection $collection, mixed $element): bool
    {
        if ($this->isCollectionInitialized($collection) === false) {
            return false;
        }

        if ($collection->contains($element) === true) {
            return false;
        }

        $collection->add($element);

        return true;
    }

    /**
     * @param Collection<array-key, T> $collection
     * @param T                        $element
     *
     * @return bool true if element was actually removed, false otherwise
     *
     * @template T
     */
    public function removeElementIfInitialized(Collection $collection, mixed $element): bool
    {
        if (! $this->isCollectionInitialized($collection)) {
            return false;
        }

        return $collection->removeElement($element);
    }

    /**
     * @param iterable<T>                                 $items
     * @param callable(T, EntityManagerInterface): void   $itemCallback
     * @param callable(EntityManagerInterface): void|null $flushCallback
     *
     * @template T
     */
    public function batch(iterable $items, int $batchSize, callable $itemCallback, ?callable $flushCallback = null): void
    {
        $batchSize = max($batchSize, 1);
        $i = 0;

        foreach ($items as $item) {
            $itemCallback($item, $this->em);

            if (++$i % $batchSize !== 0) {
                continue;
            }

            $this->em->flush();

            if ($flushCallback === null) {
                continue;
            }

            $flushCallback($this->em);
        }

        // flush objects remaining from the last unfinished batch
        $this->em->flush();

        if ($flushCallback === null) {
            return;
        }

        $flushCallback($this->em);
    }

    /**
     * Returns the ClassMetadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)).
     *
     * @param class-string<E> $className
     *
     * @return ClassMetadata<object>
     *
     * @template  E of AbstractEntity
     */
    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->em->getClassMetadata($className);
    }
}
