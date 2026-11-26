<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\QueryHandlers;

use App\Model\DTO\Stat\UsageOverview;
use App\Model\Stat\ReadModel\Queries\UsageStatisticsQuery;
use App\Model\User\Entity\UserLogin;
use App\Model\User\Entity\UserPreference;
use IntegrationTest;

use function hash;

/**
 * Runs the handler's SQL against a real MySQL: the window functions behind the
 * percentiles and the dark mode share and the WEEKDAY mapping only tell the truth
 * when the database itself evaluates them.
 */
class UsageStatisticsQueryHandlerTest extends IntegrationTest
{
    private const YEAR = 2026;
    private const MONTH = 3;

    private UsageStatisticsQueryHandler $handler;

    /**
     * Only what the handler's SQL actually reads.
     *
     * @return string[]
     */
    public function getTestedAggregateRoots(): array
    {
        return [
            UserLogin::class,
            UserPreference::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->handler = new UsageStatisticsQueryHandler($this->entityManager->getConnection());
    }

    public function testMissingDataYieldsAnEmptyButAvailableOverview(): void
    {
        $usage = $this->overview();

        self::assertTrue($usage->logins->available);
        self::assertFalse($usage->logins->hasData());
        self::assertSame(0, $usage->logins->logins);
        self::assertNull($usage->logins->sessionMedianSeconds);
        self::assertSame(0, $usage->logins->getMonthlyPeak());
        self::assertNull($usage->logins->getDarkModeShare());
        self::assertSame(0, $usage->preferences->users);
    }

    /** The administration sees the whole system: every unit counts, only the period limits the logins. */
    public function testLoginsOfAllUnitsAreCountedInThePeriod(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2026-03-02 09:00:00');
        $this->insertLogin(userId: 1, loggedInAt: '2026-03-03 09:00:00');
        $this->insertLogin(userId: 2, loggedInAt: '2026-03-04 09:00:00');
        $this->insertLogin(userId: 3, loggedInAt: '2026-12-31 23:59:59');
        // Previous and next year — outside the period entirely.
        $this->insertLogin(userId: 4, loggedInAt: '2025-12-31 23:59:59');
        $this->insertLogin(userId: 4, loggedInAt: '2027-01-01 00:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(4, $logins->logins);
        self::assertSame(3, $logins->users);
        self::assertEqualsWithDelta(4 / 3, $logins->getSessionsPerUser(), 0.001);
    }

    public function testOneMonthLimitsTheLoginsToThatMonth(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2026-02-28 23:59:59');
        $this->insertLogin(userId: 2, loggedInAt: '2026-03-01 00:00:00');
        $this->insertLogin(userId: 3, loggedInAt: '2026-03-31 23:59:59');
        $this->insertLogin(userId: 4, loggedInAt: '2026-04-01 00:00:00');

        $logins = $this->overview(self::MONTH)->logins;

        self::assertSame(2, $logins->logins);
        self::assertSame(2, $logins->users);
        self::assertSame(2, $logins->newUsers);
        self::assertSame(2, $logins->monthly[self::MONTH]);
        self::assertSame(0, $logins->monthly[2]);
    }

    /** Each user counts once, by the last login of the period that reported its theme. */
    public function testDarkModeShareFollowsTheLastReportedLoginOfEachUser(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2026-03-02 09:00:00', darkMode: false);
        $this->insertLogin(userId: 1, loggedInAt: '2026-03-05 09:00:00', darkMode: true);
        $this->insertLogin(userId: 1, loggedInAt: '2026-03-06 09:00:00');
        $this->insertLogin(userId: 2, loggedInAt: '2026-03-02 09:00:00', darkMode: true);
        $this->insertLogin(userId: 2, loggedInAt: '2026-03-03 09:00:00', darkMode: false);
        $this->insertLogin(userId: 3, loggedInAt: '2026-03-03 09:00:00', darkMode: false);
        $this->insertLogin(userId: 4, loggedInAt: '2026-03-03 09:00:00');
        // Outside the year — must not change anybody's answer.
        $this->insertLogin(userId: 2, loggedInAt: '2027-01-03 09:00:00', darkMode: true);

        $logins = $this->overview()->logins;

        self::assertSame(3, $logins->themeUsers);
        self::assertSame(1, $logins->darkModeUsers);
        self::assertEqualsWithDelta(100 / 3, $logins->getDarkModeShare(), 0.001);
    }

    /** Someone who first appeared in an earlier year is not new. */
    public function testOnlyAFirstEverLoginMakesAUserNew(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2025-11-01 09:00:00');
        $this->insertLogin(userId: 1, loggedInAt: '2026-01-10 09:00:00');
        $this->insertLogin(userId: 2, loggedInAt: '2026-01-11 09:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(2, $logins->users);
        self::assertSame(1, $logins->newUsers);
        self::assertSame(1, $logins->getReturningUsers());
    }

    public function testSessionPercentilesComeFromActiveTimeNotTokenLifetime(): void
    {
        // Active spans of 60, 120, 180, 240 and 3000 seconds.
        $this->insertLogin(userId: 1, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:01:00');
        $this->insertLogin(userId: 2, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:02:00');
        $this->insertLogin(userId: 3, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:03:00');
        $this->insertLogin(userId: 4, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:04:00');
        $this->insertLogin(userId: 5, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:50:00');

        $logins = $this->overview()->logins;

        self::assertSame(180, $logins->sessionMedianSeconds);
        self::assertSame(3000, $logins->sessionP90Seconds);
    }

    public function testSessionsWithoutLogoutAreReportedSeparately(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2026-05-01 09:00:00', endReason: UserLogin::END_REASON_LOGOUT);
        $this->insertLogin(userId: 2, loggedInAt: '2026-05-02 09:00:00');
        $this->insertLogin(userId: 3, loggedInAt: '2026-05-03 09:00:00');
        $this->insertLogin(userId: 4, loggedInAt: '2026-05-04 09:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(1, $logins->endedByLogout);
        self::assertSame(3, $logins->getEndedWithoutLogout());
        self::assertEqualsWithDelta(25.0, $logins->getLogoutShare(), 0.001);
    }

    public function testDeviceBrowserAndPlatformAreBrokenDownByLoginCount(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2026-06-01 09:00:00', deviceType: 'desktop', browser: 'Chrome', browserVersion: '130', platform: 'Windows');
        $this->insertLogin(userId: 2, loggedInAt: '2026-06-02 09:00:00', deviceType: 'desktop', browser: 'Chrome', browserVersion: '130', platform: 'Windows');
        $this->insertLogin(userId: 3, loggedInAt: '2026-06-03 09:00:00', deviceType: 'mobile', browser: 'Safari', browserVersion: null, platform: 'iOS');

        $logins = $this->overview()->logins;

        self::assertSame(['desktop' => 2, 'mobile' => 1], $logins->deviceTypes);
        self::assertSame(['Chrome 130' => 2, 'Safari' => 1], $logins->browsers);
        self::assertSame(['Windows' => 2, 'iOS' => 1], $logins->platforms);
    }

    /** MySQL WEEKDAY() starts at Monday = 0; the map has to come back Monday = 1. */
    public function testWeekMapPutsMondayFirst(): void
    {
        // 2026-08-24 is a Monday, 2026-08-30 a Sunday.
        $this->insertLogin(userId: 1, loggedInAt: '2026-08-24 08:00:00');
        $this->insertLogin(userId: 2, loggedInAt: '2026-08-24 08:30:00');
        $this->insertLogin(userId: 3, loggedInAt: '2026-08-30 21:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(2, $logins->weekMap[1][8]);
        self::assertSame(1, $logins->weekMap[7][21]);
        self::assertSame(0, $logins->weekMap[3][8]);
        self::assertSame(3, $logins->getWeekTotal());
        self::assertSame(2, $logins->getWeekPeak());
    }

    public function testPreferenceAdoptionCountsEveryoneWhoSavedSettings(): void
    {
        $this->insertPreference(userId: 1, showHelp: true, extendLogin: true, rememberRole: false);
        $this->insertPreference(userId: 2, showHelp: true, extendLogin: false, rememberRole: false);
        $this->insertPreference(userId: 3, showHelp: false, extendLogin: false, rememberRole: true);
        $this->insertPreference(userId: 4, showHelp: false, extendLogin: false, rememberRole: false);

        $preferences = $this->overview()->preferences;

        self::assertSame(4, $preferences->users);
        self::assertSame(2, $preferences->showHelp);
        self::assertSame(1, $preferences->extendLogin);
        self::assertSame(1, $preferences->rememberRole);
    }

    public function testMonthlyLoginsAlwaysCoverTwelveMonths(): void
    {
        $this->insertLogin(userId: 1, loggedInAt: '2026-01-15 10:00:00');
        $this->insertLogin(userId: 2, loggedInAt: '2026-01-16 10:00:00');
        $this->insertLogin(userId: 3, loggedInAt: '2026-07-01 10:00:00');
        $this->insertLogin(userId: 4, loggedInAt: '2026-07-02 10:00:00');
        // Outside the year — may not reach the chart.
        $this->insertLogin(userId: 5, loggedInAt: '2025-07-03 10:00:00');

        $logins = $this->overview()->logins;

        self::assertCount(12, $logins->monthly);
        self::assertSame(2, $logins->monthly[1]);
        self::assertSame(2, $logins->monthly[7]);
        self::assertSame(0, $logins->monthly[2]);
        self::assertSame(2, $logins->getMonthlyPeak());
    }

    private function overview(?int $month = null): UsageOverview
    {
        return ($this->handler)(new UsageStatisticsQuery(self::YEAR, $month));
    }

    /** The user is stored only as a hash, any 64 hex characters will do here. */
    private function insertLogin(
        int $userId,
        string $loggedInAt,
        ?string $lastSeenAt = null,
        ?string $endReason = null,
        string $deviceType = 'desktop',
        string $browser = 'Chrome',
        ?string $browserVersion = '130',
        string $platform = 'Windows',
        ?bool $darkMode = null,
    ): void {
        $this->tester->haveInDatabase('user_login', [
            'user_hash' => hash('sha256', 'user:'.$userId),
            'logged_in_at' => $loggedInAt,
            'last_seen_at' => $lastSeenAt ?? $loggedInAt,
            'logged_out_at' => $endReason === null ? null : $lastSeenAt ?? $loggedInAt,
            'end_reason' => $endReason,
            'device_type' => $deviceType,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'platform' => $platform,
            'dark_mode' => $darkMode === null ? null : (int) $darkMode,
        ]);
    }

    private function insertPreference(int $userId, bool $showHelp, bool $extendLogin, bool $rememberRole): void
    {
        $this->tester->haveInDatabase('user_preference', [
            'user_id' => $userId,
            'show_help' => $showHelp ? 1 : 0,
            'extend_skautis_login' => $extendLogin ? 1 : 0,
            'remember_skautis_role' => $rememberRole ? 1 : 0,
            'remembered_skautis_role_id' => null,
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }
}
