<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Common\Services\EventBus;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\GoogleService;
use App\Model\Google\InvalidOAuth;
use App\Model\Google\OAuthId;
use App\Model\Google\OAuthMailer;
use App\Model\Google\OAuthValidity;
use App\Model\Payment\DomainEvents\OAuthWasRestored;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Google_Client;
use Mockery as m;
use Mockery\MockInterface;
use Nette\Mail\Mailer;
use Psr\Log\NullLogger;

use function str_contains;

class MailerFactoryTest extends Unit
{
    /** @var Connection&MockInterface */
    private Connection $connection;

    /** @var EventBus&MockInterface */
    private EventBus $eventBus;

    protected function _before(): void
    {
        $this->connection = m::mock(Connection::class);
        $this->eventBus = m::mock(EventBus::class);
    }

    protected function _after(): void
    {
        m::close();
    }

    public function testInDisabledModeReturnsDebugMailer(): void
    {
        $googleService = m::mock(GoogleService::class);
        $mailer = m::mock(Mailer::class);
        $factory = $this->factory($mailer, false, $googleService);

        $this->assertSame($mailer, $factory->create($this->getConfig()));
    }

    public function testInEnabledModeReturnsSmtpMailer(): void
    {
        $googleService = m::mock(GoogleService::class, [
            'getClient' => m::mock(Google_Client::class, [
                'fetchAccessTokenWithRefreshToken' => ['token' => 'MyToken'],
                'setAccessToken' => null,
            ]),
        ]);
        $mailer = m::mock(Mailer::class);
        $factory = $this->factory($mailer, true, $googleService);

        $this->assertInstanceOf(OAuthMailer::class, $factory->create($this->getConfig()));
    }

    public function testAccessTokenIsRefreshedOncePerOAuth(): void
    {
        $client = m::mock(Google_Client::class, ['setAccessToken' => null]);
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->twice()->andReturn(['token' => 'MyToken']);
        $factory = $this->factory(m::mock(Mailer::class), true, m::mock(GoogleService::class, ['getClient' => $client]));
        $oAuth = $this->getConfig();
        $otherOAuth = $this->getConfig();

        $mailer = $factory->create($oAuth);

        $this->assertSame($mailer, $factory->create($oAuth));
        $this->assertNotSame($mailer, $factory->create($otherOAuth));
    }

    /** Google refusing the token itself means the account has to be connected again, so it is remembered. */
    public function testRefusedAccountIsRememberedAsInvalid(): void
    {
        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->with(m::on(static fn (string $sql): bool => str_contains($sql, 'SET invalid_since = ?')), m::any(), m::any())
            ->andReturn(1);

        $exception = $this->createRefused(['error' => 'invalid_grant', 'error_description' => 'Bad Request']);

        self::assertTrue($exception->isRevoked());
        self::assertSame('invalid_grant => Bad Request', $exception->getMessage());
        self::assertStringNotContainsString('Bad Request', $exception->getExplainedMessage());
    }

    /** Other refusals (e.g. a wrong client of the application) are no fault of the account, nothing is remembered. */
    public function testOtherRefusalIsNotRemembered(): void
    {
        $this->connection->shouldNotReceive('executeStatement');

        $exception = $this->createRefused(['error' => 'invalid_client', 'error_description' => 'Unauthorized']);

        self::assertFalse($exception->isRevoked());
    }

    /** A refused account that Google accepts again (e.g. a test e-mail after a fix on its side) works again. */
    public function testAcceptedInvalidAccountIsRestored(): void
    {
        $client = m::mock(Google_Client::class, ['setAccessToken' => null, 'fetchAccessTokenWithRefreshToken' => ['token' => 'MyToken']]);
        $factory = $this->factory(m::mock(Mailer::class), true, m::mock(GoogleService::class, ['getClient' => $client]));
        $this->connection->shouldReceive('executeStatement')
            ->once()
            ->with(m::on(static fn (string $sql): bool => str_contains($sql, 'SET invalid_since = NULL')), m::any())
            ->andReturn(1);
        $this->eventBus->shouldReceive('handle')->once()->with(m::type(OAuthWasRestored::class));

        $factory->create($this->getConfig(invalid: true));
    }

    /** @param array<string, string> $response */
    private function createRefused(array $response): InvalidOAuth
    {
        $client = m::mock(Google_Client::class, ['fetchAccessTokenWithRefreshToken' => $response]);
        $factory = $this->factory(m::mock(Mailer::class), true, m::mock(GoogleService::class, ['getClient' => $client]));

        try {
            $factory->create($this->getConfig());
        } catch (InvalidOAuth $exception) {
            return $exception;
        }

        self::fail('The refused account must not get a mailer.');
    }

    private function factory(Mailer $mailer, bool $enabled, GoogleService $googleService): MailerFactory
    {
        $entityManager = m::mock(EntityManagerInterface::class, ['getConnection' => $this->connection]);

        return new MailerFactory($mailer, $enabled, $googleService, new OAuthValidity($entityManager, $this->eventBus, new NullLogger()));
    }

    private function getConfig(bool $invalid = false): GoogleOAuth
    {
        $mock = m::mock(GoogleOAuth::class);
        $mock->shouldReceive(['getToken' => 'XXX', 'getId' => OAuthId::generate(), 'getEmail' => 'test@skaut.cz', 'isInvalid' => $invalid]);

        return $mock;
    }
}
