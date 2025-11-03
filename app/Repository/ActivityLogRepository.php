<?php

declare(strict_types=1);

namespace Repository;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Entity\ActivityLog;
use Ramsey\Uuid\UuidInterface;
use Skautis\User;

/**
 * @template TEntity of object
 * @template TLog of ActivityLog<TEntity>
 * @phpstan-type ProcessedActivityLog array{id: UuidInterface, createdAt: DateTimeImmutable, message: string, loginName: string, loginMasked: bool, loginEmpty: bool, loginLink: string|null}
 * @phpstan-type ProcessedLogin array{loginName: string, loginMasked: bool, loginEmpty: bool, loginLink: string|null}
 * @extends AbstractRepository<ActivityLog>
 */
abstract class ActivityLogRepository extends AbstractRepository
{
    public const EMPTY_LOGIN = '--';

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    /** @return class-string<TLog> */
    abstract public function getActivityLogClass(): string;

    public function getEntityClass(): string
    {
        return $this->getActivityLogClass();
    }

    protected function andWhereTarget(QueryBuilder $query, string|null $alias = null): void
    {
        if ($alias !== null) {
            return;
        }

        [$alias] = $query->getRootAliases();
    }

    /**
     * @param  ActivityLog<object> $log
     *
     * @return ProcessedActivityLog
     */
    protected function getProcessedValues(ActivityLog $log): array
    {
        return [
            'id' => $log->getId(),
            'createdAt' => $log->getCreatedAt(),
            'message' => $log->getMessage(),
            ...$this->getProcessedLogin($log->getLogin()),
        ];
    }

    /** @return ProcessedLogin */
    protected function getProcessedLogin(User|null $login): array
    {
        $loginName   = self::EMPTY_LOGIN;
        $loginEmpty  = true;
        $loginMasked = false;
        $loginLink   = null;

        //@TODO

        return [
            'loginName' => $loginName,
            'loginEmpty' => $loginEmpty,
            'loginMasked' => $loginMasked,
            'loginLink' => $loginLink,
        ];
    }
}
