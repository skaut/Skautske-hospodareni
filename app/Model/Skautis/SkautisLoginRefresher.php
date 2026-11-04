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
use Throwable;

use function str_contains;

/**
 * Extends the SkautIS login at most once per minute, after the response is sent.
 */
final class SkautisLoginRefresher
{
    private const LOGIN_LENGTH = '+30 minutes';

    /** 29 minutes left means the last extension happened more than 60 seconds ago. */
    private const EXTEND_WHEN_REMAINING_BELOW = 29 * 60;

    private const DEAD_LOGIN_TTL = '30 minutes';

    private Cache $cache;

    public function __construct(
        private User $user,
        private WebServiceInterface $userWebService,
        Storage $storage,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
        $this->cache = new Cache($storage, 'skautis.deadLogin');
    }

    /**
     * Writes the optimistic logout time, runs $beforeSkautisCall (close the session, finish the response)
     * and then calls SkautIS, but only when the last extension is more than a minute old.
     *
     * @param callable(): void $beforeSkautisCall
     */
    public function extend(callable $beforeSkautisCall): void
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
            $this->userWebService->call('LoginUpdateRefresh', ['ID' => $loginId]);
        } catch (AuthenticationException) {
            $this->markDead($loginId);
        } catch (WsdlException $exception) {
            // SkautIS rejects an unknown login with "Neplatný login", which the library does not map to AuthenticationException.
            if (str_contains($exception->getMessage(), 'Neplatný login')) {
                $this->markDead($loginId);

                return;
            }

            $this->logger->warning('SkautIS login extension failed.', ['exception' => $exception]);
        } catch (Throwable $exception) {
            // The next due extension, a minute later, tries again.
            $this->logger->warning('SkautIS login extension failed.', ['exception' => $exception]);
        }
    }

    private function markDead(string $loginId): void
    {
        $this->cache->save($loginId, true, [Cache::Expire => self::DEAD_LOGIN_TTL]);
    }

    /** Was this login rejected by SkautIS during an earlier background extension? */
    public function isLoginDead(): bool
    {
        $loginId = $this->user->getLoginId();

        return $loginId !== null && $this->cache->load($loginId) === true;
    }
}
