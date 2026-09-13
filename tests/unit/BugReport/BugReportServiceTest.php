<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\BugReport\Manager\TechnicalErrorReportManager;
use App\Model\Mail\SystemMailer;
use App\Model\Services\TemplateFactory;
use App\Model\User\SkautisRole;
use App\Model\User\UserService;
use Codeception\Test\Unit;
use Latte\Engine;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Mockery as m;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Nette\Utils\FileSystem as FileSystemUtil;
use ReflectionProperty;
use RuntimeException;
use stdClass;

/**
 * Hlášení technické chyby sbírá diagnostiku z několika zdrojů, které mohou selhat.
 * Test jede přes reálný notifikační servis (jen s falešným mailerem), takže ověřuje i to,
 * že se hlášení skutečně rozešle.
 */
final class BugReportServiceTest extends Unit
{
    private const RELEASE = 'abc1234';

    public function testSubmitCollectsUserRequestAndBrowserDiagnostics(): void
    {
        $manager = new BugReportServiceManagerSpy();
        $mailer = $this->createMailer();

        $report = $this->createService($manager, $mailer)->submit(
            '  Nejde uložit doklad  ',
            '  https://moje-hospodareni.cz/ucetnictvi  ',
            ['viewport' => '1920x1080'],
        );

        self::assertSame('Nejde uložit doklad', $report->getDescription());
        self::assertSame('https://moje-hospodareni.cz/ucetnictvi', $report->getReportedUrl());
        self::assertSame(42, $report->getReporterUserId());
        self::assertSame('Jana Kvapilová', $report->getReporterDisplayName());
        self::assertSame('jana@example.test', $report->getReporterEmail());
        self::assertSame('Hospodář střediska', $report->getRoleName());
        self::assertSame(99, $report->getRoleId());
        self::assertSame(7, $report->getUnitId());
        self::assertSame('Středisko Test', $report->getUnitName());
        self::assertSame(self::RELEASE, $report->getAppRelease());
        self::assertSame('Mozilla/5.0 (Test)', $report->getUserAgent());
        self::assertSame('10.0.0.9', $report->getIpAddress());

        $diagnostics = $report->getDiagnostics();
        self::assertSame(42, $diagnostics['user']['id']);
        self::assertSame(99, $diagnostics['user']['activeRole']['id']);
        self::assertSame('Středisko Test', $diagnostics['user']['activeRole']['unitName']);
        self::assertSame([5, 6], $diagnostics['user']['accessibleUnitIds'][UserService::ACCESS_READ]);
        self::assertSame([5], $diagnostics['user']['accessibleUnitIds'][UserService::ACCESS_EDIT]);
        self::assertSame([['ID' => 99, 'Name' => 'Hospodář střediska']], $diagnostics['user']['allRoles']);
        self::assertSame('GET', $diagnostics['request']['method']);
        self::assertSame('10.0.0.9', $diagnostics['request']['remoteAddress']);
        self::assertSame('Mozilla/5.0 (Test)', $diagnostics['request']['headers']['User-Agent']);
        self::assertSame('cs', $diagnostics['request']['headers']['Accept-Language']);
        self::assertArrayNotHasKey('Cookie', $diagnostics['request']['headers'], 'citlivé hlavičky se nesbírají');
        self::assertArrayNotHasKey('Referer', $diagnostics['request']['headers'], 'prázdné hlavičky se vynechají');
        self::assertSame(self::RELEASE, $diagnostics['application']['release']);
        self::assertSame(['viewport' => '1920x1080'], $diagnostics['browser']);
        self::assertSame([], $diagnostics['collectionErrors']);

        self::assertInstanceOf(Message::class, $mailer->message, 'hlášení se odeslalo e-mailem');
        self::assertTrue($report->wasNotificationSent());
        self::assertSame(1, $manager->savedNotificationStates);
    }

    public function testFailedNotificationIsRecordedOnTheReport(): void
    {
        $mailer = new BugReportServiceMailerSpy('SMTP je mimo');

        $report = $this->createService(new BugReportServiceManagerSpy(), $mailer)->submit('Popis', null, []);

        self::assertFalse($report->wasNotificationSent());
        self::assertSame('SMTP je mimo', $report->getNotificationError());
        self::assertNull($report->getReportedUrl());
    }

    public function testUnavailableSkautisDegradesToFallbacksAndRecordsErrors(): void
    {
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUserDetail')->andThrow(new RuntimeException('Skautis je mimo'));
        $userService->shouldReceive('getRoleId')->andThrow(new RuntimeException('Role není dostupná'));
        $userService->shouldReceive('getPersonalDetail')->andThrow(new RuntimeException('Skautis je mimo'));

        $report = $this->createService(new BugReportServiceManagerSpy(), $this->createMailer(), $userService)
            ->submit('Popis', null, [], ' zaloha@example.test ');

        self::assertSame('Uživatel 42', $report->getReporterDisplayName(), 'jméno se nahradí ID uživatele');
        self::assertSame('zaloha@example.test', $report->getReporterEmail(), 'použije se e-mail z formuláře');
        self::assertNull($report->getRoleId());

        $errors = $report->getDiagnostics()['collectionErrors'];
        self::assertCount(2, $errors);
        self::assertStringContainsString('userDetail: RuntimeException: Skautis je mimo', $errors[0]);
        self::assertStringContainsString('roleId: RuntimeException: Role není dostupná', $errors[1]);
    }

    public function testInvalidReporterEmailIsDiscarded(): void
    {
        $report = $this->createService(new BugReportServiceManagerSpy(), $this->createMailer(), $this->createEmptyUserService())
            ->submit('Popis', '   ', [], 'tohle-není-e-mail');

        self::assertNull($report->getReporterEmail());
        self::assertNull($report->getReportedUrl(), 'prázdná URL se neukládá');
    }

    public function testReporterEmailFallsBackToPersonalDetail(): void
    {
        $personalDetail = new stdClass();
        $personalDetail->Email = 'osobni@example.test';

        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUserDetail')->andReturn(new stdClass());
        $userService->shouldReceive('getRoleId')->andReturn(99);
        $userService->shouldReceive('getPersonalDetail')->andReturn($personalDetail);

        $service = $this->createService(new BugReportServiceManagerSpy(), $this->createMailer(), $userService);

        self::assertSame('osobni@example.test', $service->getCurrentReporterEmail());
    }

    public function testCurrentReporterEmailPrefersUserDetail(): void
    {
        $service = $this->createService(new BugReportServiceManagerSpy(), $this->createMailer());

        self::assertSame('jana@example.test', $service->getCurrentReporterEmail());
    }

    public function testCurrentReporterEmailIsNullWhenNothingIsAvailable(): void
    {
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUserDetail')->andThrow(new RuntimeException('Skautis je mimo'));
        $userService->shouldReceive('getPersonalDetail')->andThrow(new RuntimeException('Skautis je mimo'));

        $service = $this->createService(new BugReportServiceManagerSpy(), $this->createMailer(), $userService);

        self::assertNull($service->getCurrentReporterEmail());
    }

    public function testSubmitRequiresAuthenticatedUser(): void
    {
        $service = $this->createService(
            new BugReportServiceManagerSpy(),
            $this->createMailer(),
            null,
            $this->userWithIdentity(null),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A technical error report requires an authenticated user.');

        $service->submit('Popis', null, []);
    }

    public function testIdentityWithoutSkautisRoleReportsNoActiveRole(): void
    {
        $identity = new SimpleIdentity(42, [], ['currentRole' => 'nesmysl', 'access' => 'nesmysl']);

        $report = $this->createService(
            new BugReportServiceManagerSpy(),
            $this->createMailer(),
            null,
            $this->userWithIdentity($identity),
        )->submit('Popis', null, []);

        self::assertNull($report->getRoleName());
        self::assertNull($report->getDiagnostics()['user']['activeRole']);
        self::assertSame([], $report->getDiagnostics()['user']['accessibleUnitIds'][UserService::ACCESS_READ]);
    }

    private function createService(
        TechnicalErrorReportManager $manager,
        Mailer $mailer,
        ?UserService $userService = null,
        ?User $user = null,
    ): BugReportService {
        $uploadDirectory = codecept_output_dir('bug-report-service');
        FileSystemUtil::createDir($uploadDirectory);

        return new BugReportService(
            $manager,
            $this->createNotificationService($mailer, $uploadDirectory),
            $user ?? $this->createUser(),
            $userService ?? $this->createUserService(),
            $this->createRequest(),
            new BugReportScreenshotStorage(
                new Filesystem(new LocalFilesystemAdapter($uploadDirectory)),
                $uploadDirectory,
            ),
            self::RELEASE,
        );
    }

    private function createNotificationService(Mailer $mailer, string $uploadDirectory): BugReportNotificationService
    {
        return new BugReportNotificationService(
            new SystemMailer(
                $mailer,
                false,
                false,
                'https://moje-hospodareni.cz',
                new TemplateFactory(new class implements LatteFactory {
                    public function create(?Control $control = null): Engine
                    {
                        return new Engine();
                    }
                }),
            ),
            ['admin@example.test'],
            'https://moje-hospodareni.cz',
            new BugReportScreenshotStorage(
                new Filesystem(new LocalFilesystemAdapter($uploadDirectory)),
                $uploadDirectory,
            ),
        );
    }

    private function createMailer(): BugReportServiceMailerSpy
    {
        return new BugReportServiceMailerSpy();
    }

    private function createUserService(): UserService
    {
        $userDetail = new stdClass();
        $userDetail->Person = 'Jana Kvapilová';
        $userDetail->Email = 'jana@example.test';

        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUserDetail')->andReturn($userDetail);
        $userService->shouldReceive('getRoleId')->andReturn(99);
        $userService->shouldReceive('getPersonalDetail')->andReturn(new stdClass());

        return $userService;
    }

    private function createEmptyUserService(): UserService
    {
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('getUserDetail')->andReturn(new stdClass());
        $userService->shouldReceive('getRoleId')->andReturn(99);
        $userService->shouldReceive('getPersonalDetail')->andReturn(new stdClass());

        return $userService;
    }

    private function createUser(): User
    {
        $role = new stdClass();
        $role->ID = 99;
        $role->Name = 'Hospodář střediska';

        $identity = new SimpleIdentity(42, [], [
            'currentRole' => new SkautisRole('unit', 'Hospodář střediska', 7, 'Středisko Test'),
            'access' => [
                UserService::ACCESS_READ => [5 => 'Středisko', 6 => 'Oddíl'],
                UserService::ACCESS_EDIT => [5 => 'Středisko'],
            ],
            'skautisRoles' => [$role],
        ]);

        return $this->userWithIdentity($identity);
    }

    /**
     * Nette\Security\User::getIdentity() je final, takže se nedá mockovat — identitu proto
     * podstrčíme přes vlastní UserStorage, stejně jako to dělá aplikace za běhu.
     */
    private function userWithIdentity(?IIdentity $identity): User
    {
        return new User(new class($identity) implements UserStorage {
            public function __construct(private ?IIdentity $identity)
            {
            }

            public function saveAuthentication(IIdentity $identity): void
            {
            }

            public function clearAuthentication(bool $clearIdentity): void
            {
            }

            /** @return array{bool, ?IIdentity, ?int} */
            public function getState(): array
            {
                return [$this->identity !== null, $this->identity, null];
            }

            public function setExpiration(?string $expire, bool $clearIdentity): void
            {
            }
        });
    }

    private function createRequest(): Request
    {
        return new Request(
            new UrlScript('https://moje-hospodareni.cz/ucetnictvi'),
            [],
            [],
            [],
            [
                'User-Agent' => 'Mozilla/5.0 (Test)',
                'Accept-Language' => 'cs',
                'Referer' => '',
                'Cookie' => 'session=tajne',
            ],
            'GET',
            '10.0.0.9',
        );
    }
}

final class BugReportServiceMailerSpy implements Mailer
{
    public ?Message $message = null;

    public function __construct(private ?string $failWith = null)
    {
    }

    public function send(Message $mail): void
    {
        if ($this->failWith !== null) {
            throw new RuntimeException($this->failWith);
        }

        $this->message = $mail;
    }
}

/**
 * Nahrazuje persistenci: hlášení jen podrží a doplní mu ID, které Doctrine jinak přiřadí při flush
 * (bez ID neprojde ani odeslání notifikace).
 */
final class BugReportServiceManagerSpy extends TechnicalErrorReportManager
{
    public int $savedNotificationStates = 0;

    public function __construct()
    {
    }

    public function create(TechnicalErrorReport $report): TechnicalErrorReport
    {
        $id = new ReflectionProperty($report, 'id');
        $id->setAccessible(true);
        $id->setValue($report, 123);

        return $report;
    }

    public function saveNotificationState(TechnicalErrorReport $report): void
    {
        ++$this->savedNotificationStates;
    }
}
