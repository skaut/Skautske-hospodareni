<?php

declare(strict_types=1);

namespace App\Model\User\Services;

use App\Model\Common\Services\KeyedHasher;
use App\Model\User\DeviceInfo;
use App\Model\User\Entity\UserLogin;
use App\Model\User\Manager\UserLoginManager;
use App\Model\User\Repository\UserLoginRepository;
use DateTimeImmutable;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_int;
use function time;

/**
 * Records logins for the usage statistics in the administration.
 *
 * Nothing here may ever break a request: every entry point swallows its errors
 * and logs them. A statistic is not a reason for someone to lose their login.
 */
final class LoginTracker
{
    /**
     * How stale last_seen_at is allowed to get. Without this every request
     * would carry an UPDATE, and the metric only needs minute resolution.
     */
    private const TOUCH_INTERVAL_SECONDS = 60;

    private const SESSION_SECTION = 'userLoginTracking';

    /** Logins keep only this hash of the user, never the user. */
    private const USER_HASH_PURPOSE = 'usage-user';

    public function __construct(
        private Session $session,
        private IRequest $httpRequest,
        private UserLoginRepository $repository,
        private UserLoginManager $manager,
        private DeviceClassifier $deviceClassifier,
        private KeyedHasher $hasher,
        private LoggerInterface $logger,
    ) {
    }

    public function recordLogin(int $userId): void
    {
        try {
            $userHash = $this->hasher->hash(self::USER_HASH_PURPOSE, $userId);

            if ($userHash === null) {
                $this->logger->warning('Přihlášení se nezaznamenává: chybí klíč APP_KEY.');

                return;
            }

            if (! $this->repository->isStorageAvailable()) {
                return;
            }

            $login = $this->manager->start($userHash, $this->classifyCurrentDevice());

            $section = $this->session->getSection(self::SESSION_SECTION);
            $section->set('loginId', $login->getId());
            $section->set('touchedAt', time());
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se zaznamenat přihlášení pro statistiky využití.', ['exception' => $e]);
        }
    }

    /**
     * Marks the current request as activity. Callers must not invoke this for
     * the session keep-alive ping — a timer firing on its own is not a person
     * working, and counting it would stretch every opted-in user's session to
     * the length of the browser tab being open.
     */
    public function touch(): void
    {
        try {
            $section = $this->session->getSection(self::SESSION_SECTION);
            $loginId = $section->get('loginId');

            if (! is_int($loginId)) {
                return;
            }

            $touchedAt = $section->get('touchedAt');
            $now = time();

            if (is_int($touchedAt) && $now - $touchedAt < self::TOUCH_INTERVAL_SECONDS) {
                return;
            }

            $login = $this->repository->findById($loginId);

            if (! $login instanceof UserLogin) {
                $section->remove();

                return;
            }

            $this->manager->touch($login, new DateTimeImmutable());
            $section->set('touchedAt', $now);
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se aktualizovat aktivitu pro statistiky využití.', ['exception' => $e]);
        }
    }

    /**
     * Stores the theme the user switched to with the button, on the current login. Only a click is known to
     * the server: the choice itself is kept in the browser.
     */
    public function recordTheme(bool $darkMode): void
    {
        try {
            $loginId = $this->session->getSection(self::SESSION_SECTION)->get('loginId');

            if (! is_int($loginId)) {
                return;
            }

            $login = $this->repository->findById($loginId);

            if (! $login instanceof UserLogin) {
                return;
            }

            $this->manager->reportTheme($login, $darkMode);
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se zaznamenat tmavý režim pro statistiky využití.', ['exception' => $e]);
        }
    }

    public function recordLogout(): void
    {
        try {
            $section = $this->session->getSection(self::SESSION_SECTION);
            $loginId = $section->get('loginId');
            $section->remove();

            if (! is_int($loginId)) {
                return;
            }

            $login = $this->repository->findById($loginId);

            if (! $login instanceof UserLogin) {
                return;
            }

            $this->manager->finish($login, new DateTimeImmutable());
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se zaznamenat odhlášení pro statistiky využití.', ['exception' => $e]);
        }
    }

    private function classifyCurrentDevice(): DeviceInfo
    {
        return $this->deviceClassifier->classify(
            $this->httpRequest->getHeader('User-Agent'),
            $this->httpRequest->getHeader('Sec-CH-UA-Mobile'),
            $this->httpRequest->getHeader('Sec-CH-UA-Platform'),
        );
    }
}
