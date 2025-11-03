<?php

declare(strict_types=1);

namespace Manager;

use Doctrine\ORM\EntityManagerInterface;
use Entity\ActivityLog;
use Model\UserService;
use Throwable;

use function debug_backtrace;
use function dump;
use function dumpe;

/**
 * @template TEntity of object
 * @template TLog of ActivityLog<TEntity>
 */
abstract class ActivityLogManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager, private UserService $userService)
    {
        parent::__construct($entityManager);
    }

    /** @return class-string<TLog> */
    abstract public function getActivityLogClass(): string;

    public function getEntityClass(): string
    {
        dumpe(debug_backtrace());

        return $this->getActivityLogClass();
    }

    /** @template T of ActivityLog */
    public function create(ActivityLog $activityLog): ActivityLog
    {
        if ($activityLog->getUser() === null) {
            try {
                $activityLog->setUser($this->userService->getUser());
            } catch (Throwable) {
                // Nothing to assign
            }
        }

        dump($activityLog);
        $this->saveEntity($activityLog);

        return $activityLog;
    }
}
