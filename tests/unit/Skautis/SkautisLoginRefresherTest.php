<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use Codeception\Test\Unit;
use DateTime;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Nette\Caching\Storages\MemoryStorage;
use Psr\Clock\ClockInterface;
use Psr\Log\NullLogger;
use Skautis\User;
use Skautis\Wsdl\AuthenticationException;
use Skautis\Wsdl\WebServiceInterface;
use Skautis\Wsdl\WsdlException;
use Skautis\Wsdl\WsdlManager;
use stdClass;

use function ini_get;

final class SkautisLoginRefresherTest extends Unit
{
    private const LOGIN_ID = 'login-id';
    private const USER_ID = 7;
    private const NOW = '2026-11-04 12:00:00';

    private User $user;

    private WebServiceInterface&MockInterface $webService;

    private MemoryStorage $storage;

    private SkautisLoginRefresher $refresher;

    protected function _before(): void
    {
        $this->user = new User(Mockery::mock(WsdlManager::class));
        $this->webService = Mockery::mock(WebServiceInterface::class);
        $this->storage = new MemoryStorage();
        $this->refresher = $this->createRefresher();
    }

    public function testRecentlyExtendedLoginIsNotExtendedAgain(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:29:30');
        $this->webService->shouldNotReceive('call');

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertEquals(new DateTime('2026-11-04 12:29:30'), $this->user->getLogoutDate());
    }

    public function testDueLoginIsExtendedAfterOptimisticLogoutTimeAndSessionClose(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:28:59');
        $steps = [];
        $this->webService->shouldReceive('call')
            ->once()
            ->with('LoginUpdateRefresh', [['ID' => self::LOGIN_ID]]) // WebService::call() reads the SOAP input from $arguments[0]
            ->andReturnUsing(static function () use (&$steps): object {
                $steps[] = 'skautis call';

                return (object) ['DateLogout' => '2026-11-04T12:30:00.123'];
            });
        $this->webService->shouldReceive('call')->with('UserRoleAll', Mockery::any())->andReturn([]);

        $this->refresher->extend(self::USER_ID, function () use (&$steps): void {
            $steps[] = 'session closed, logout at '.$this->user->getLogoutDate()->format('Y-m-d H:i:s');
        });

        self::assertSame(['session closed, logout at 2026-11-04 12:30:00', 'skautis call'], $steps);
    }

    public function testLoginRejectedBySkautisIsDeadOnNextRequest(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        $this->webService->shouldReceive('call')->once()->andThrow(new AuthenticationException());

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertTrue($this->createRefresher()->isLoginDead());
    }

    public function testUnknownLoginIsDeadOnNextRequest(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        // Message returned by test-is.skaut.cz for LoginUpdateRefresh with an unknown login ID.
        $this->webService->shouldReceive('call')->once()->andThrow(new WsdlException(
            "Server was unable to process request. ---> Neplatný login\nPři editaci objektu UM_Login nastala chyba",
        ));

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertTrue($this->createRefresher()->isLoginDead());
    }

    public function testOtherSkautisFailureDoesNotMarkLoginDeadNorEscape(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        $this->webService->shouldReceive('call')->once()->andThrow(new WsdlException('Could not connect to host'));

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertFalse($this->createRefresher()->isLoginDead());
    }

    public function testRolesLoadedWithExtensionStayUntilForgotten(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        $roles = [(object) ['ID' => 10], (object) ['ID' => 20]];
        $this->webService->shouldReceive('call')->once()->with('LoginUpdateRefresh', Mockery::any());
        $this->webService->shouldReceive('call')
            ->once()
            ->with('UserRoleAll', [['ID_User' => self::USER_ID, 'IsActive' => true]])
            ->andReturn($roles);

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        $nextRequest = $this->createRefresher();
        self::assertEquals($roles, $nextRequest->freshRoles());
        self::assertEquals($roles, $nextRequest->freshRoles(), 'Not applied yet, so the next request tries again.');

        $nextRequest->forgetFreshRoles();
        self::assertNull($nextRequest->freshRoles());
    }

    public function testUserWithoutActiveRoleGetsEmptyRoles(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        $this->webService->shouldReceive('call')->once()->with('LoginUpdateRefresh', Mockery::any());
        // SkautIS returns the bare result object instead of a list when there is no role.
        $this->webService->shouldReceive('call')->once()->with('UserRoleAll', Mockery::any())->andReturn(new stdClass());

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertSame([], $this->createRefresher()->freshRoles());
    }

    public function testFailedExtensionLoadsNoRoles(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        $this->webService->shouldReceive('call')->once()->andThrow(new WsdlException('Could not connect to host'));

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertNull($this->createRefresher()->freshRoles());
    }

    public function testLoginIsNotDeadWithoutFailedExtension(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:29:30');

        self::assertFalse($this->refresher->isLoginDead());
    }

    public function testLoginExtendedExactlyMinuteAgoIsNotExtended(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:29:00');
        $this->webService->shouldNotReceive('call');

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertEquals(new DateTime('2026-11-04 12:29:00'), $this->user->getLogoutDate());
    }

    public function testSkautisCallsRunWithShortSocketTimeout(): void
    {
        $this->logInWithLogoutAt('2026-11-04 12:10:00');
        $timeoutBefore = ini_get('default_socket_timeout');
        $timeouts = [];
        $this->webService->shouldReceive('call')->andReturnUsing(static function () use (&$timeouts): array {
            $timeouts[] = ini_get('default_socket_timeout');

            throw new WsdlException('Error Fetching http headers');
        });

        $this->refresher->extend(self::USER_ID, static function (): void {
        });

        self::assertSame(['10'], $timeouts, 'A hanging SkautIS holds the PHP-FPM worker only this long.');
        self::assertSame($timeoutBefore, ini_get('default_socket_timeout'));
    }

    public function testNothingHappensWithoutLogin(): void
    {
        $this->webService->shouldNotReceive('call');
        $closed = false;

        $this->refresher->extend(self::USER_ID, static function () use (&$closed): void {
            $closed = true;
        });

        self::assertFalse($closed);
        self::assertFalse($this->refresher->isLoginDead());
    }

    private function logInWithLogoutAt(string $logoutDate): void
    {
        $this->user->setLoginData(self::LOGIN_ID, 1, 2, new DateTime($logoutDate));
    }

    private function createRefresher(): SkautisLoginRefresher
    {
        $clock = new class(new DateTimeImmutable(self::NOW)) implements ClockInterface {
            public function __construct(private DateTimeImmutable $now)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->now;
            }
        };

        return new SkautisLoginRefresher($this->user, $this->webService, $this->storage, $clock, new NullLogger());
    }
}
