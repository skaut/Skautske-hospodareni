<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use DateTime;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Skautis\User;
use Skautis\Wsdl\AuthenticationException;
use Skautis\Wsdl\WebServiceInterface;
use Skautis\Wsdl\WsdlException;
use stdClass;
use Throwable;

use function str_contains;

/**
 * Extends the SkautIS login at most once per minute, after the response is sent,
 * and reloads the user's SkautIS roles in the same step, so the next request sees a role that ended.
 */
final class SkautisLoginRefresher
{
    private const LOGIN_LENGTH = '+30 minutes';

    /** 29 minutes left means the last extension happened more than 60 seconds ago. */
    private const EXTEND_WHEN_REMAINING_BELOW = 29 * 60;

    private const CACHE_TTL = '30 minutes';

    private Cache $cache;

    public function __construct(
        private User $user,
        private WebServiceInterface $userWebService,
        Storage $storage,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
        $this->cache = new Cache($storage, 'skautis.login');
    }

    /**
     * Writes the optimistic logout time, runs $beforeSkautisCall (close the session, finish the response)
     * and then calls SkautIS, but only when the last extension is more than a minute old.
     *
     * @param int              $userId            SkautIS user ID, the ID of the Nette identity
     * @param callable(): void $beforeSkautisCall
     */
    public function extend(int $userId, callable $beforeSkautisCall): void
    {
        $loginId = $this->user->getLoginId();
        $now = $this->clock->now();
        /** @var DateTime|null $logoutDate the library's PHPDoc omits null */
        $logoutDate = $this->user->getLogoutDate();

        if ($loginId === null || $logoutDate !== null && $logoutDate->getTimestamp() - $now->getTimestamp() >= self::EXTEND_WHEN_REMAINING_BELOW) {
            return;
        }

        $this->user->updateLoginData(null, null, null, DateTime::createFromImmutable($now)->modify(self::LOGIN_LENGTH));
        $beforeSkautisCall();

        try {
            // Directly through the web service: Skautis\User::updateLogoutTime() writes to the already closed session.
            $this->userWebService->call('LoginUpdateRefresh', [['ID' => $loginId]]);
            $roles = $this->userWebService->call('UserRoleAll', [['ID_User' => $userId, 'IsActive' => true]]);
            $this->cache->save('roles.'.$loginId, $roles instanceof stdClass ? [] : $roles, [Cache::Expire => self::CACHE_TTL]);
        } catch (AuthenticationException) {
            $this->markDead($loginId);
        } catch (WsdlException $exception) {
            // SkautIS rejects an unknown login with "Neplatný login", which the library does not map to AuthenticationException.
            if (str_contains($exception->getMessage(), 'Neplatný login')) {
                $this->markDead($loginId);

                return;
            }

            $this->logger->error('SkautIS login extension failed.', ['exception' => $exception]);
        } catch (Throwable $exception) {
            // The optimistic logout time stays (the session is closed); the next due extension, a minute later, tries again.
            $this->logger->error('SkautIS login extension failed.', ['exception' => $exception]);
        }
    }

    private function markDead(string $loginId): void
    {
        $this->cache->save('dead.'.$loginId, true, [Cache::Expire => self::CACHE_TTL]);
    }

    /** Was this login rejected by SkautIS during an earlier background extension? */
    public function isLoginDead(): bool
    {
        $loginId = $this->user->getLoginId();

        return $loginId !== null && $this->cache->load('dead.'.$loginId) === true;
    }

    /**
     * Roles loaded by the last background extension and not yet applied.
     *
     * @return stdClass[]|null null when there is nothing new since they were last applied
     */
    public function freshRoles(): ?array
    {
        $loginId = $this->user->getLoginId();

        return $loginId === null ? null : $this->cache->load('roles.'.$loginId);
    }

    /** Call once the fresh roles are applied, so later requests skip them. */
    public function forgetFreshRoles(): void
    {
        $loginId = $this->user->getLoginId();
        if ($loginId !== null) {
            $this->cache->remove('roles.'.$loginId);
        }
    }
}
