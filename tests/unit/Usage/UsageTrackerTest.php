<?php

declare(strict_types=1);

namespace App\Model\Usage\Services;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use App\Model\Unit\UnitNotFound;
use App\Model\Usage\Repository\UsageRepository;
use Codeception\Test\Unit as TestCase;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use Psr\Log\NullLogger;
use RuntimeException;
use Skautis\Skautis;
use Skautis\User;

use function sys_get_temp_dir;

final class UsageTrackerTest extends TestCase
{
    private const TROOP_ID = 101;
    private const OTHER_UNIT_ID = 202;
    private const MISSING_UNIT_ID = 303;

    private Session $session;

    /** @var list<array{string, string, string, string}> */
    private array $increments = [];

    private bool $failIncrement = false;

    private string $loginId = 'login-1';

    private int $unitId = self::TROOP_ID;

    private int $unitLookups = 0;

    /** The PHP session is global, what one scenario counted must not leak into the next one. */
    protected function _after(): void
    {
        if ($this->session->isStarted()) {
            $this->session->destroy();
        }

        Mockery::close();
    }

    public function testASessionCountsOnceInTheSystemAndUnderItsUnit(): void
    {
        $tracker = $this->tracker();

        $tracker->record('Payments:GroupList', 'default');
        self::assertSame([
            ['', '', '', ''],
            ['', 'payments', '', ''],
            ['', 'payments', 'Payments:GroupList', ''],
            ['', 'payments', 'Payments:GroupList', 'Payments:GroupList:default'],
            ['622.01', '', '', ''],
            ['622.01', 'payments', '', ''],
            ['622.01', 'payments', 'Payments:GroupList', ''],
        ], $this->increments);

        $this->increments = [];
        $tracker->record('Payments:GroupList', 'default');
        self::assertSame([], $this->increments, 'Návrat na stránku se započítal znovu');

        $tracker->record('Payments:Payment', 'default');
        self::assertSame([
            ['', 'payments', 'Payments:Payment', ''],
            ['', 'payments', 'Payments:Payment', 'Payments:Payment:default'],
            ['622.01', 'payments', 'Payments:Payment', ''],
        ], $this->increments);

        $this->increments = [];
        $tracker->record('Travel:Default', 'default');
        self::assertCount(5, $this->increments);
        self::assertSame(1, $this->unitLookups, 'Jednotka se má zjistit jednou za přihlášení a roli');
    }

    public function testAdministrationIsNotCounted(): void
    {
        $this->tracker()->record('Admin:Usage', 'default');

        self::assertSame([], $this->increments);
    }

    /** The whole system and the pages count the login once, the new unit gets its own session. */
    public function testSwitchingTheRoleToAnotherUnitCountsOnlyUnderThatUnit(): void
    {
        $tracker = $this->tracker();
        $tracker->record('Payments:GroupList', 'default');

        $this->increments = [];
        $this->unitId = self::OTHER_UNIT_ID;
        $tracker->record('Payments:GroupList', 'default');

        self::assertSame([
            ['611.02', '', '', ''],
            ['611.02', 'payments', '', ''],
            ['611.02', 'payments', 'Payments:GroupList', ''],
        ], $this->increments);
    }

    public function testAUnitSkautisDoesNotTellAboutCountsAsUnknownAndIsNotLookedUpAgain(): void
    {
        $tracker = $this->tracker();
        $this->unitId = self::MISSING_UNIT_ID;

        $tracker->record('Payments:GroupList', 'default');
        $tracker->record('Payments:Payment', 'default');

        self::assertSame(['', '', '', ''], $this->increments[0]);
        self::assertContains(['neurceno', '', '', ''], $this->increments);
        self::assertSame(1, $this->unitLookups);
    }

    public function testANewLoginCountsAgain(): void
    {
        $tracker = $this->tracker();
        $tracker->record('Payments:GroupList', 'default');

        $this->increments = [];
        $this->loginId = 'login-2';
        $tracker->record('Payments:GroupList', 'default');

        self::assertCount(7, $this->increments);
    }

    /** A failure must not break the page, and what was not stored is tried again on the next request. */
    public function testAStorageFailureIsSwallowedAndRetried(): void
    {
        $tracker = $this->tracker();

        $this->failIncrement = true;
        $tracker->record('Payments:GroupList', 'default');
        self::assertSame([], $this->increments);

        $this->failIncrement = false;
        $tracker->record('Payments:GroupList', 'default');
        self::assertCount(7, $this->increments);
    }

    private function tracker(): UsageTracker
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getLoginId')->andReturnUsing(fn (): string => $this->loginId);
        $user->shouldReceive('getUnitId')->andReturnUsing(fn (): int => $this->unitId);

        $skautis = Mockery::mock(Skautis::class);
        $skautis->shouldReceive('getUser')->andReturn($user);

        $units = Mockery::mock(IUnitRepository::class);
        $units->shouldReceive('find')->andReturnUsing(function (int $id): Unit {
            ++$this->unitLookups;

            if ($id === self::MISSING_UNIT_ID) {
                throw new UnitNotFound('Unit not found.');
            }

            return $this->unit($id, $id === self::TROOP_ID ? '622.01.093' : '611.02', $id === self::TROOP_ID ? 'oddil' : 'stredisko');
        });

        /** @var UsageRepository&MockInterface $repository */
        $repository = Mockery::mock(UsageRepository::class);
        $repository->shouldReceive('isStorageAvailable')->andReturn(true);
        $repository->shouldReceive('increment')->andReturnUsing(
            function (DateTimeImmutable $day, string $unitCode, string $section, string $screen, string $page): void {
                if ($this->failIncrement) {
                    throw new RuntimeException('Database is gone.');
                }

                $this->increments[] = [$unitCode, $section, $screen, $page];
            },
        );

        $sessionSavePath = sys_get_temp_dir().'/hskauting-usage-tracker-test-sessions';
        FileSystem::createDir($sessionSavePath);
        $this->session = (new Session(new Request(new UrlScript('http://localhost/')), new Response()))->setSavePath($sessionSavePath);

        return new UsageTracker($skautis, $units, $repository, $this->session, new NullLogger());
    }

    private function unit(int $id, string $registrationNumber, string $type): Unit
    {
        return new Unit($id, 'Jednotka', 'Jednotka', 'IČ', 'Ulice', 'Město', '10000', $registrationNumber, $type, null, []);
    }
}
