<?php

declare(strict_types=1);

namespace App\Model\Google;

use App\Model\Common\Services\EventBus;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Payment\DomainEvents\OAuthWasRestored;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * Remembers which connected Google accounts Google refuses, so the application stops sending from them
 * and tells people to connect the account again.
 *
 * It writes straight to the database: the account is usually loaded in a request that sends e-mails, and
 * nothing else of that request may be saved with it.
 */
final class OAuthValidity
{
    private const REASON_MAX_LENGTH = 255;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBus $eventBus,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Only the token Google refused is marked: when the account was connected again meanwhile, its new
     * token works. The first refusal keeps its time.
     */
    public function markInvalid(GoogleOAuth $oAuth, InvalidOAuth $exception, DateTimeImmutable $now): void
    {
        $marked = $this->entityManager->getConnection()->executeStatement(
            'UPDATE google_oauth SET invalid_since = ?, invalid_reason = ? WHERE id = ? AND token = ? AND invalid_since IS NULL',
            [$now, Strings::truncate($exception->getMessage(), self::REASON_MAX_LENGTH, ''), $oAuth->getId()->toString(), $oAuth->getToken()],
            [Types::DATETIME_IMMUTABLE, Types::STRING, Types::STRING, Types::STRING],
        );

        if ($marked === 0) {
            return;
        }

        // Not an error of the application: the owner of the account has to connect it again.
        $this->logger->warning(sprintf('Google refused the connected account %s: %s', $oAuth->getId()->toString(), $exception->getMessage()));
    }

    /** Google accepted the token again, e.g. a test e-mail went out after a fix on the side of Google. */
    public function markValid(OAuthId $oAuthId): void
    {
        $restored = $this->entityManager->getConnection()->executeStatement(
            'UPDATE google_oauth SET invalid_since = NULL, invalid_reason = NULL WHERE id = ? AND invalid_since IS NOT NULL',
            [$oAuthId->toString()],
        );

        if ($restored === 0) {
            return;
        }

        $this->eventBus->handle(new OAuthWasRestored($oAuthId));
    }
}
