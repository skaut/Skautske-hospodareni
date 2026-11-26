<?php

declare(strict_types=1);

namespace App\Model\Google;

use App\Model\Common\Services\EventBus;
use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Infrastructure\Repositories\Mail\GoogleRepository;
use App\Model\Payment\DomainEvents\OAuthWasRestored;
use DateTimeImmutable;
use IntegrationTest;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

/** A Google account that Google refuses is remembered until it works again. */
final class OAuthValidityTest extends IntegrationTest
{
    /** @var EventBus&MockInterface */
    private EventBus $eventBus;

    private OAuthValidity $validity;

    private GoogleRepository $repository;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [GoogleOAuth::class];
    }

    protected function _before(): void
    {
        parent::_before();

        $this->eventBus = Mockery::spy(EventBus::class);
        $this->validity = new OAuthValidity($this->entityManager, $this->eventBus, new NullLogger());
        $this->repository = new GoogleRepository($this->entityManager);
    }

    protected function _after(): void
    {
        Mockery::close();

        parent::_after();
    }

    public function testRefusedAccountKeepsTheTimeOfTheFirstRefusal(): void
    {
        $oAuth = $this->createOAuth();

        $this->validity->markInvalid($oAuth, new InvalidOAuth('test@skaut.cz', 'invalid_grant', 'Bad Request'), new DateTimeImmutable('2026-11-09 10:00:00'));
        $this->validity->markInvalid($oAuth, new InvalidOAuth('test@skaut.cz', 'invalid_grant', 'Bad Request'), new DateTimeImmutable('2026-11-10 10:00:00'));

        $reloaded = $this->reload($oAuth);
        self::assertTrue($reloaded->isInvalid());
        self::assertSame('2026-11-09 10:00:00', $reloaded->getInvalidSince()?->format('Y-m-d H:i:s'));
        self::assertSame('invalid_grant => Bad Request', $reloaded->getInvalidReason());
    }

    /** Someone connected the account again while the refused request ran, so its new token works. */
    public function testAccountConnectedAgainMeanwhileIsNotMarked(): void
    {
        $oAuth = $this->createOAuth();
        $this->entityManager->getConnection()->executeStatement('UPDATE google_oauth SET token = ? WHERE id = ?', ['new-token', $oAuth->getId()->toString()]);

        $this->validity->markInvalid($oAuth, new InvalidOAuth('test@skaut.cz', 'invalid_grant'), new DateTimeImmutable());

        self::assertFalse($this->reload($oAuth)->isInvalid());
    }

    public function testRestoredAccountIsAnnouncedOnce(): void
    {
        $oAuth = $this->createOAuth();
        $this->validity->markInvalid($oAuth, new InvalidOAuth('test@skaut.cz', 'invalid_grant'), new DateTimeImmutable());

        $this->validity->markValid($oAuth->getId());
        $this->validity->markValid($oAuth->getId());

        $reloaded = $this->reload($oAuth);
        self::assertFalse($reloaded->isInvalid());
        self::assertNull($reloaded->getInvalidReason());
        $this->eventBus->shouldHaveReceived('handle')->with(Mockery::type(OAuthWasRestored::class))->once();
    }

    /** Connecting the account again gives a new token, which Google accepts. */
    public function testNewTokenClearsTheMark(): void
    {
        $oAuth = $this->createOAuth();
        $this->validity->markInvalid($oAuth, new InvalidOAuth('test@skaut.cz', 'invalid_grant'), new DateTimeImmutable());

        $reloaded = $this->reload($oAuth);
        $reloaded->setToken('new-token');
        $this->repository->save($reloaded);

        self::assertFalse($this->reload($oAuth)->isInvalid());
    }

    private function createOAuth(): GoogleOAuth
    {
        $oAuth = GoogleOAuth::create(new UnitId(123), 'token', 'test@skaut.cz');
        $this->repository->save($oAuth);

        return $oAuth;
    }

    private function reload(GoogleOAuth $oAuth): GoogleOAuth
    {
        $this->entityManager->clear();

        return $this->repository->find($oAuth->getId());
    }
}
