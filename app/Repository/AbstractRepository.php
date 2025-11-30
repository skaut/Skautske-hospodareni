<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;

use function assert;
use function is_array;
use function is_callable;
use function is_int;

/**
 * @template T of object
 * @extends EntityRepository<T>
 * @phpstan-type AssociationCriteria array<string, array<string, array<string, array<string, array<string, mixed>>>>>
 * @phpstan-type QueryByCriteria string|Comparison|Composite|Func|callable(Expr $expr): (string|Comparison|Composite|Func)
 * @phpstan-type QueryByParameters array<string, mixed>
 */
abstract class AbstractRepository extends EntityRepository
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $entityClass = $this->getEntityClass();
        /** @var ClassMetadata<T> $metadata */
        $metadata = $entityManager->getClassMetadata($entityClass);
        $this->entityManager = $entityManager;
        parent::__construct($entityManager, $metadata);
    }

    /**
     * @return T
     *
     * @throws NoResultException
     */
    public function findOrFail(mixed $id): object
    {
        /** @var T|null $result */
        $result = $this->find($id);

        if ($result === null) {
            throw new NoResultException();
        }

        return $result;
    }

    /**
     * [
     *     childAssociation1 => [
     *         grandChildAssociation1 => [
     *             youGetIt => [],
     *         ],
     *         grandChildAssociation2 => [],
     *     ],
     *     childAssociation2 => [],
     * ].
     *
     * @param AssociationCriteria $associations
     * @phpstan-param AbstractQuery::HYDRATE_*|null $hydrationMode
     *
     * @phpstan-return ($hydrationMode is null ? T : mixed)
     *
     * @throws NoResultException
     */
    public function findOrFailWithEagerLoad(mixed $id, array $associations, ?int $hydrationMode = null): mixed
    {
        if (empty($associations)) {
            return $this->findOrFail($id);
        }

        $query = $this
            ->createQueryBuilder('entity')
            ->andWhere('entity.id = :id')
            ->setParameter('id', $id);

        $this->applyAssociationCriteria($query, 'entity', $associations);

        return $query->getQuery()->getSingleResult($hydrationMode);
    }

    /** @param AssociationCriteria $associations */
    protected function applyAssociationCriteria(QueryBuilder $query, string $parentAlias, array $associations): void
    {
        foreach ($associations as $associationName => $childAssociations) {
            $associationAlias = "_$associationName";
            $query
                ->addSelect($associationAlias)
                ->leftJoin("$parentAlias.$associationName", $associationAlias);

            if (! is_array($childAssociations)) {
                continue;
            }

            $this->applyAssociationCriteria($query, $associationAlias, $childAssociations);
        }
    }

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return T
     *
     * @throws NoResultException
     */
    public function findOneByOrFail(array $criteria, ?array $orderBy = null): object
    {
        /** @var T|null $result */
        $result = $this->findOneBy($criteria, $orderBy);

        if ($result === null) {
            throw new NoResultException();
        }

        return $result;
    }

    /**
     * @return T
     *
     * @throws ORMException
     */
    public function getReferenceOrFail(mixed $id): object
    {
        $reference = $this
            ->getEntityManager()
            ->getReference($this->getEntityClass(), $id);

        if ($reference === null) {
            throw new NoResultException();
        }

        return $reference;
    }

    /** @return array<int, mixed> */
    public function getSingleColumnUniqueValues(string $column): array
    {
        return $this
            ->createQueryBuilder('entity')
            ->select("entity.{$column}")
            ->groupBy("entity.{$column}")
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function andWhereDeleted(QueryBuilder $builder, ?bool $deleted = false, ?string $alias = null): void
    {
        if ($alias === null) {
            [$alias] = $builder->getRootAliases();
        }

        if ($deleted === null) {
            return;
        }

        $builder->andWhere("$alias.deleted = :deleted")->setParameter('deleted', $deleted);
    }

    /** @return iterable<T> */
    public function findAllIterable(): iterable
    {
        return $this
            ->createQueryBuilder('entity')
            ->getQuery()
            ->toIterable();
    }

    /** @return array<string, mixed>[] */
    public function findAllScalar(string ...$select): array
    {
        return $this
            ->createQueryBuilder('entity')
            ->select($select)
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return iterable<T>
     */
    public function findByIterable(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): iterable
    {
        // This is the same logic as for EntityRepository::findBy(), but modified to return iterable instead
        $entityManager = $this->getEntityManager();
        $entityPersister = $entityManager->getUnitOfWork()->getEntityPersister($this->getEntityClass());
        $connection = $entityManager->getConnection();

        $sql = $entityPersister->getSelectSQL($criteria, limit: $limit, offset: $offset, orderBy: $orderBy);
        [$params, $types] = $entityPersister->expandParameters($criteria);
        $stmt = $connection->executeQuery($sql, $params, $types);

        $hydrator = $entityManager->newHydrator(AbstractQuery::HYDRATE_OBJECT);
        $rsm = $entityPersister->getResultSetMapping();

        return $hydrator->toIterable($stmt, $rsm, [Query::HINT_INTERNAL_ITERATION => true]);
    }

    /** @return int<0, max> */
    public function getCount(): int
    {
        try {
            $count = (int) $this
                ->createQueryBuilder('entity')
                ->select('COUNT(entity)')
                ->getQuery()
                ->getSingleScalarResult();
            assert(is_int($count) && $count >= 0);

            return $count;
        } catch (NoResultException) {
            return 0;
        }
    }

    /**
     * @param Comparison        $criteria
     * @param QueryByParameters $parameters
     */
    public function getQueryBy(callable|string|Comparison|Composite|Func $criteria, array $parameters = [], string $alias = 'entity'): QueryBuilder
    {
        $query = $this->createQueryBuilder($alias);
        $query->where(is_callable($criteria) ? $criteria($query->expr()) : $criteria);

        foreach ($parameters as $key => $value) {
            $query->setParameter($key, $value);
        }

        return $query;
    }

    /**
     * @param Comparison        $criteria
     * @param QueryByParameters $parameters
     *
     * @return int<0, max>
     */
    public function getCountBy(callable|string|Comparison|Composite|Func $criteria, array $parameters = [], string $alias = 'entity'): int
    {
        try {
            $count = (int) $this
                ->getQueryBy($criteria, $parameters, $alias)
                ->select("COUNT({$alias})")
                ->getQuery()
                ->getSingleScalarResult();
            assert(is_int($count) && $count >= 0);

            return $count;
        } catch (NoResultException) {
            return 0;
        }
    }

    /** @return class-string */
    abstract public function getEntityClass(): string;

    protected function createUnexpectedNonUniqueResultException(NonUniqueResultException $e): RuntimeException
    {
        return new RuntimeException('Unexpected NonUniqueResultException', previous: $e);
    }
}
