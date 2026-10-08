<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\QueryHandlers;

use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\DTO\Stat\UsageOverview;
use App\Model\Logger\LogEntry;
use App\Model\Payment\Group;
use App\Model\Stat\ReadModel\Queries\UsageStatisticsQuery;
use App\Model\User\Entity\PaymentGroupVisit;
use App\Model\User\Entity\UserLogin;
use App\Model\User\Entity\UserPreference;
use App\Model\User\Services\DeviceClassifier;
use IntegrationTest;

/**
 * Runs the handler's SQL against a real MySQL: the window function behind the
 * percentiles, the WEEKDAY mapping and the multi-table joins only tell the truth
 * when the database itself evaluates them.
 */
class UsageStatisticsQueryHandlerTest extends IntegrationTest
{
    private const UNIT_ID = 10;
    private const OTHER_UNIT_ID = 99;
    private const YEAR = 2026;

    private UsageStatisticsQueryHandler $handler;

    /**
     * Only what the handler's SQL actually reads. Invoices used to be listed here
     * for the monthly chart; that chart now counts logins, so keeping the entity
     * would build a large schema the queries never touch.
     *
     * @return string[]
     */
    public function getTestedAggregateRoots(): array
    {
        return [
            UserLogin::class,
            UserPreference::class,
            PaymentGroupVisit::class,
            Group::class,
            BankTransactionPairing::class,
            TechnicalErrorReport::class,
            LogEntry::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->handler = new UsageStatisticsQueryHandler(
            $this->entityManager->getConnection(),
            new DeviceClassifier(),
        );
    }

    public function testMissingDataYieldsAnEmptyButAvailableOverview(): void
    {
        $usage = $this->overview();

        self::assertTrue($usage->logins->available);
        self::assertFalse($usage->logins->hasData());
        self::assertSame(0, $usage->logins->logins);
        self::assertNull($usage->logins->sessionMedianSeconds);
        self::assertSame(0, $usage->logins->getMonthlyPeak());
        self::assertNull($usage->engagement->getAutomaticPairingShare());
        self::assertNull($usage->preferences->getShowHelpShare());
    }

    public function testLoginsAreCountedForTheUnitTreeAndTheWholeSystem(): void
    {
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-03-02 09:00:00');
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-03-03 09:00:00');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-03-04 09:00:00');
        // Another unit — outside the filter, but still part of the system total.
        $this->insertLogin(userId: 3, unitId: self::OTHER_UNIT_ID, loggedInAt: '2026-03-05 09:00:00');
        // Previous year — outside the period entirely.
        $this->insertLogin(userId: 4, unitId: self::UNIT_ID, loggedInAt: '2025-03-05 09:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(3, $logins->logins);
        self::assertSame(2, $logins->users);
        self::assertSame(1, $logins->units);
        self::assertSame(4, $logins->loginsAllUnits);
        self::assertSame(3, $logins->usersAllUnits);
        self::assertEqualsWithDelta(1.5, $logins->getSessionsPerUser(), 0.001);
    }

    /** Someone who first appeared in an earlier year is not new, even in a unit they just joined. */
    public function testOnlyAFirstEverLoginMakesAUserNew(): void
    {
        $this->insertLogin(userId: 1, unitId: self::OTHER_UNIT_ID, loggedInAt: '2025-11-01 09:00:00');
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-01-10 09:00:00');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-01-11 09:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(2, $logins->users);
        self::assertSame(1, $logins->newUsers);
        self::assertSame(1, $logins->getReturningUsers());
    }

    public function testSessionPercentilesComeFromActiveTimeNotTokenLifetime(): void
    {
        // Active spans of 60, 120, 180, 240 and 3000 seconds.
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:01:00');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:02:00');
        $this->insertLogin(userId: 3, unitId: self::UNIT_ID, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:03:00');
        $this->insertLogin(userId: 4, unitId: self::UNIT_ID, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:04:00');
        $this->insertLogin(userId: 5, unitId: self::UNIT_ID, loggedInAt: '2026-04-01 09:00:00', lastSeenAt: '2026-04-01 09:50:00');

        $logins = $this->overview()->logins;

        self::assertSame(180, $logins->sessionMedianSeconds);
        self::assertSame(3000, $logins->sessionP90Seconds);
    }

    public function testSessionsWithoutLogoutAreReportedSeparately(): void
    {
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-05-01 09:00:00', endReason: UserLogin::END_REASON_LOGOUT);
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-05-02 09:00:00');
        $this->insertLogin(userId: 3, unitId: self::UNIT_ID, loggedInAt: '2026-05-03 09:00:00');
        $this->insertLogin(userId: 4, unitId: self::UNIT_ID, loggedInAt: '2026-05-04 09:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(1, $logins->endedByLogout);
        self::assertSame(3, $logins->getEndedWithoutLogout());
        self::assertEqualsWithDelta(25.0, $logins->getLogoutShare(), 0.001);
    }

    public function testDeviceBrowserAndPlatformAreBrokenDownByLoginCount(): void
    {
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-06-01 09:00:00', deviceType: 'desktop', browser: 'Chrome', browserVersion: '130', platform: 'Windows');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-06-02 09:00:00', deviceType: 'desktop', browser: 'Chrome', browserVersion: '130', platform: 'Windows');
        $this->insertLogin(userId: 3, unitId: self::UNIT_ID, loggedInAt: '2026-06-03 09:00:00', deviceType: 'mobile', browser: 'Safari', browserVersion: null, platform: 'iOS');

        $logins = $this->overview()->logins;

        self::assertSame(['desktop' => 2, 'mobile' => 1], $logins->deviceTypes);
        self::assertSame(['Chrome 130' => 2, 'Safari' => 1], $logins->browsers);
        self::assertSame(['Windows' => 2, 'iOS' => 1], $logins->platforms);
    }

    public function testRoleKeysAreGroupedThroughTheDomainRules(): void
    {
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-06-01 09:00:00', roleKey: 'vedouciStredisko');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-06-02 09:00:00', roleKey: 'vedouciOddil');
        $this->insertLogin(userId: 3, unitId: self::UNIT_ID, loggedInAt: '2026-06-03 09:00:00', roleKey: 'hospodarStredisko');
        $this->insertLogin(userId: 4, unitId: self::UNIT_ID, loggedInAt: '2026-06-04 09:00:00', roleKey: null);

        $logins = $this->overview()->logins;

        self::assertSame(
            ['Vedoucí' => 2, 'Hospodář' => 1, 'Neurčeno' => 1],
            $logins->roleGroups,
        );
    }

    /** MySQL WEEKDAY() starts at Monday = 0; the map has to come back Monday = 1. */
    public function testWeekMapPutsMondayFirst(): void
    {
        // 2026-08-24 is a Monday, 2026-08-30 a Sunday.
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-08-24 08:00:00');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-08-24 08:30:00');
        $this->insertLogin(userId: 3, unitId: self::UNIT_ID, loggedInAt: '2026-08-30 21:00:00');

        $logins = $this->overview()->logins;

        self::assertSame(2, $logins->weekMap[1][8]);
        self::assertSame(1, $logins->weekMap[7][21]);
        self::assertSame(0, $logins->weekMap[3][8]);
        self::assertSame(3, $logins->getWeekTotal());
        self::assertSame(2, $logins->getWeekPeak());
    }

    public function testPreferenceAdoptionIgnoresTheUnitFilter(): void
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
        self::assertEqualsWithDelta(50.0, $preferences->getShowHelpShare(), 0.001);
    }

    public function testActiveAuthorsCountDistinctPeopleInTheUnitAndYear(): void
    {
        $this->insertLogEntry(userId: 1, unitId: self::UNIT_ID, date: '2026-02-01 10:00:00');
        $this->insertLogEntry(userId: 1, unitId: self::UNIT_ID, date: '2026-02-02 10:00:00');
        $this->insertLogEntry(userId: 2, unitId: self::UNIT_ID, date: '2026-02-03 10:00:00');
        $this->insertLogEntry(userId: 3, unitId: self::OTHER_UNIT_ID, date: '2026-02-04 10:00:00');
        $this->insertLogEntry(userId: 4, unitId: self::UNIT_ID, date: '2025-02-04 10:00:00');

        self::assertSame(2, $this->overview()->engagement->activeAuthors);
    }

    public function testBotReportsAreLeftOutOfTheDeviceSample(): void
    {
        $this->insertReport('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36');
        $this->insertReport('Mozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Mobile/15E148 Safari/604.1');
        $this->insertReport('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

        $engagement = $this->overview()->engagement;

        self::assertSame(['desktop' => 1, 'mobile' => 1], $engagement->reportDeviceTypes);
        self::assertSame(2, $engagement->getReportDeviceTotal());
    }

    public function testMonthlyLoginsAlwaysCoverTwelveMonths(): void
    {
        $this->insertLogin(userId: 1, unitId: self::UNIT_ID, loggedInAt: '2026-01-15 10:00:00');
        $this->insertLogin(userId: 2, unitId: self::UNIT_ID, loggedInAt: '2026-01-16 10:00:00');
        $this->insertLogin(userId: 3, unitId: self::UNIT_ID, loggedInAt: '2026-07-01 10:00:00');
        // Outside the unit tree and outside the year — neither may reach the chart.
        $this->insertLogin(userId: 4, unitId: self::OTHER_UNIT_ID, loggedInAt: '2026-07-02 10:00:00');
        $this->insertLogin(userId: 5, unitId: self::UNIT_ID, loggedInAt: '2025-07-03 10:00:00');

        $logins = $this->overview()->logins;

        self::assertCount(12, $logins->monthly);
        self::assertSame(2, $logins->monthly[1]);
        self::assertSame(1, $logins->monthly[7]);
        self::assertSame(0, $logins->monthly[2]);
        self::assertSame(2, $logins->getMonthlyPeak());
    }

    private function overview(): UsageOverview
    {
        return ($this->handler)(new UsageStatisticsQuery([self::UNIT_ID], self::YEAR));
    }

    private function insertLogin(
        int $userId,
        int $unitId,
        string $loggedInAt,
        ?string $lastSeenAt = null,
        ?string $endReason = null,
        string $deviceType = 'desktop',
        string $browser = 'Chrome',
        ?string $browserVersion = '130',
        string $platform = 'Windows',
        ?string $roleKey = 'vedouciStredisko',
    ): void {
        $this->tester->haveInDatabase('user_login', [
            'user_id' => $userId,
            'unit_id' => $unitId,
            'role_id' => 1,
            'role_key' => $roleKey,
            'logged_in_at' => $loggedInAt,
            'last_seen_at' => $lastSeenAt ?? $loggedInAt,
            'logged_out_at' => $endReason === null ? null : $lastSeenAt ?? $loggedInAt,
            'end_reason' => $endReason,
            'device_type' => $deviceType,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'platform' => $platform,
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

    private function insertLogEntry(int $userId, int $unitId, string $date): void
    {
        $this->tester->haveInDatabase('log', [
            'unit_id' => $unitId,
            'user_id' => $userId,
            'date' => $date,
            'description' => 'změna',
            'type' => 'object',
            'type_id' => null,
        ]);
    }

    private function insertReport(string $userAgent, string $createdAt = '2026-03-01 10:00:00'): void
    {
        $this->tester->haveInDatabase('technical_error_report', [
            'description' => 'něco se rozbilo',
            'reporter_user_id' => 1,
            'reporter_display_name' => 'Tester',
            'unit_id' => self::UNIT_ID,
            'user_agent' => $userAgent,
            'app_release' => 'test',
            'diagnostics' => '{}',
            'created_at' => $createdAt,
        ]);
    }
}
