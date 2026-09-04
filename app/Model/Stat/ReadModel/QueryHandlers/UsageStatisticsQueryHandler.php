<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\QueryHandlers;

use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\DTO\Stat\EngagementSignals;
use App\Model\DTO\Stat\LoginStatistics;
use App\Model\DTO\Stat\PreferenceAdoption;
use App\Model\DTO\Stat\UsageOverview;
use App\Model\Stat\ReadModel\Queries\UsageStatisticsQuery;
use App\Model\User\DeviceInfo;
use App\Model\User\Entity\UserLogin;
use App\Model\User\Services\DeviceClassifier;
use App\Model\User\SkautisRole;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

use function array_slice;
use function arsort;
use function count;
use function sprintf;

final class UsageStatisticsQueryHandler
{
    /** Beyond this a browser row says more about the long tail than about the users. */
    private const MAX_BROWSER_ROWS = 8;

    private ?bool $loginTableExists = null;

    public function __construct(
        private Connection $connection,
        private DeviceClassifier $deviceClassifier,
    ) {
    }

    public function __invoke(UsageStatisticsQuery $query): UsageOverview
    {
        return new UsageOverview(
            $this->loginStatistics($query),
            $this->preferenceAdoption(),
            $this->engagementSignals($query),
        );
    }

    // ── Fáze 1: user_login ────────────────────────────────────────────────

    private function loginStatistics(UsageStatisticsQuery $query): LoginStatistics
    {
        if (! $this->hasLoginTable()) {
            return LoginStatistics::unavailable();
        }

        $unitIds = $query->getUnitIds();

        if ($unitIds === []) {
            return new LoginStatistics(available: true);
        }

        $totals = $this->fetchLoginTotals($unitIds, $query->getYear());
        $global = $this->fetchGlobalLoginTotals($query->getYear());

        return new LoginStatistics(
            available: true,
            logins: $totals['logins'],
            users: $totals['users'],
            units: $totals['units'],
            newUsers: $this->fetchNewUserCount($unitIds, $query->getYear()),
            loginsAllUnits: $global['logins'],
            usersAllUnits: $global['users'],
            sessionMedianSeconds: $totals['median'],
            sessionP90Seconds: $totals['p90'],
            endedByLogout: $totals['endedByLogout'],
            deviceTypes: $this->fetchLoginBreakdown($unitIds, $query->getYear(), 'device_type'),
            browsers: $this->fetchBrowserBreakdown($unitIds, $query->getYear()),
            platforms: $this->fetchLoginBreakdown($unitIds, $query->getYear(), 'platform'),
            roleGroups: $this->fetchRoleGroups($unitIds, $query->getYear()),
            weekMap: $this->fetchWeekMap($unitIds, $query->getYear()),
            monthly: $this->fetchMonthlyLogins($unitIds, $query->getYear()),
        );
    }

    /**
     * @param int[] $unitIds
     *
     * @return array{logins: int, users: int, units: int, endedByLogout: int, median: int|null, p90: int|null}
     */
    private function fetchLoginTotals(array $unitIds, int $year): array
    {
        // MySQL 8 has no PERCENTILE_CONT. Nearest-rank instead: order the sessions
        // and take the one at CEIL(p * n). PERCENT_RANK would look natural here
        // but reports the largest value *below* the percentile, so the slowest
        // session could never be the p90 no matter how far it stood out.
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS logins,
                COUNT(DISTINCT user_id) AS users,
                COUNT(DISTINCT unit_id) AS units,
                COALESCE(SUM(CASE WHEN end_reason = :logout THEN 1 ELSE 0 END), 0) AS ended_by_logout,
                MAX(CASE WHEN position = median_position THEN active_seconds END) AS median_seconds,
                MAX(CASE WHEN position = p90_position THEN active_seconds END) AS p90_seconds
            FROM (
                SELECT
                    user_id,
                    unit_id,
                    end_reason,
                    TIMESTAMPDIFF(SECOND, logged_in_at, last_seen_at) AS active_seconds,
                    ROW_NUMBER() OVER (ORDER BY TIMESTAMPDIFF(SECOND, logged_in_at, last_seen_at)) AS position,
                    CEIL(0.5 * COUNT(*) OVER ()) AS median_position,
                    CEIL(0.9 * COUNT(*) OVER ()) AS p90_position
                FROM user_login
                WHERE unit_id IN (:unitIds)
                  AND YEAR(logged_in_at) = :year
            ) ranked
SQL;

        $row = $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
            'logout' => UserLogin::END_REASON_LOGOUT,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAssociative();

        if ($row === false) {
            return ['logins' => 0, 'users' => 0, 'units' => 0, 'endedByLogout' => 0, 'median' => null, 'p90' => null];
        }

        return [
            'logins' => (int) $row['logins'],
            'users' => (int) $row['users'],
            'units' => (int) $row['units'],
            'endedByLogout' => (int) $row['ended_by_logout'],
            'median' => $row['median_seconds'] !== null ? (int) $row['median_seconds'] : null,
            'p90' => $row['p90_seconds'] !== null ? (int) $row['p90_seconds'] : null,
        ];
    }

    /** @return array{logins: int, users: int} */
    private function fetchGlobalLoginTotals(int $year): array
    {
        $row = $this->connection->executeQuery(
            'SELECT COUNT(*) AS logins, COUNT(DISTINCT user_id) AS users FROM user_login WHERE YEAR(logged_in_at) = :year',
            ['year' => $year],
            ['year' => ParameterType::INTEGER],
        )->fetchAssociative();

        if ($row === false) {
            return ['logins' => 0, 'users' => 0];
        }

        return ['logins' => (int) $row['logins'], 'users' => (int) $row['users']];
    }

    /**
     * Users whose very first login anywhere in the system falls inside the year.
     * Deliberately not "first login in this unit" — someone moving between units
     * is not a new user of the application.
     *
     * @param int[] $unitIds
     */
    private function fetchNewUserCount(array $unitIds, int $year): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*)
            FROM (
                SELECT user_id
                FROM user_login
                WHERE unit_id IN (:unitIds)
                  AND YEAR(logged_in_at) = :year
                GROUP BY user_id
            ) active
            INNER JOIN (
                SELECT user_id, MIN(logged_in_at) AS first_login
                FROM user_login
                GROUP BY user_id
            ) first_seen ON first_seen.user_id = active.user_id
            WHERE YEAR(first_seen.first_login) = :year
SQL;

        return (int) $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchOne();
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<string, int>
     */
    private function fetchLoginBreakdown(array $unitIds, int $year, string $column): array
    {
        // $column is never user input — the call sites pass literals.
        $sql = sprintf(
            'SELECT %s AS bucket, COUNT(*) AS logins
                FROM user_login
                WHERE unit_id IN (:unitIds) AND YEAR(logged_in_at) = :year
                GROUP BY %s
                ORDER BY logins DESC',
            $column,
            $column,
        );

        $rows = $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllKeyValue();

        $breakdown = [];
        foreach ($rows as $bucket => $logins) {
            $breakdown[(string) $bucket] = (int) $logins;
        }

        return $breakdown;
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<string, int>
     */
    private function fetchBrowserBreakdown(array $unitIds, int $year): array
    {
        $sql = <<<'SQL'
            SELECT
                CASE WHEN browser_version IS NULL THEN browser ELSE CONCAT(browser, ' ', browser_version) END AS bucket,
                COUNT(*) AS logins
            FROM user_login
            WHERE unit_id IN (:unitIds) AND YEAR(logged_in_at) = :year
            GROUP BY bucket
            ORDER BY logins DESC
SQL;

        $rows = $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllKeyValue();

        $breakdown = [];
        foreach ($rows as $bucket => $logins) {
            $breakdown[(string) $bucket] = (int) $logins;
        }

        return array_slice($breakdown, 0, self::MAX_BROWSER_ROWS, true);
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<string, int>
     */
    private function fetchRoleGroups(array $unitIds, int $year): array
    {
        $sql = <<<'SQL'
            SELECT COALESCE(role_key, '') AS role_key, COUNT(*) AS logins
            FROM user_login
            WHERE unit_id IN (:unitIds) AND YEAR(logged_in_at) = :year
            GROUP BY role_key
SQL;

        $rows = $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllKeyValue();

        $groups = [];
        foreach ($rows as $roleKey => $logins) {
            $label = $this->roleGroupLabel((string) $roleKey);
            $groups[$label] = ($groups[$label] ?? 0) + (int) $logins;
        }

        arsort($groups);

        return $groups;
    }

    /** Grouped through the domain's own prefix rules rather than a second copy of them. */
    private function roleGroupLabel(string $roleKey): string
    {
        if ($roleKey === '') {
            return 'Neurčeno';
        }

        $role = new SkautisRole($roleKey, '', 0, '');

        return match (true) {
            $role->isLeader() => 'Vedoucí',
            $role->isAccountant() => 'Hospodář',
            $role->isOfficer() => 'Činovník',
            $role->isEventManager() => 'Správce akcí',
            default => 'Ostatní',
        };
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, array<int, int>>
     */
    private function fetchWeekMap(array $unitIds, int $year): array
    {
        $sql = <<<'SQL'
            SELECT WEEKDAY(logged_in_at) AS weekday, HOUR(logged_in_at) AS hour_of_day, COUNT(*) AS logins
            FROM user_login
            WHERE unit_id IN (:unitIds) AND YEAR(logged_in_at) = :year
            GROUP BY weekday, hour_of_day
SQL;

        $map = [];
        for ($day = 1; $day <= 7; ++$day) {
            for ($hour = 0; $hour < 24; ++$hour) {
                $map[$day][$hour] = 0;
            }
        }

        foreach ($this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllAssociative() as $row) {
            // WEEKDAY() counts Monday as 0; the map is 1-based so the template reads naturally.
            $map[(int) $row['weekday'] + 1][(int) $row['hour_of_day']] = (int) $row['logins'];
        }

        return $map;
    }

    /**
     * When in the year people actually work. Replaces an earlier chart built from
     * groups, invoices and reports — those are domain counts that Admin:Statistics
     * already reports, and counting them again here produced a second, slightly
     * different answer to a question that page had already answered.
     *
     * @param int[] $unitIds
     *
     * @return array<int, int>
     */
    private function fetchMonthlyLogins(array $unitIds, int $year): array
    {
        $months = [];
        for ($month = 1; $month <= 12; ++$month) {
            $months[$month] = 0;
        }

        $sql = <<<'SQL'
            SELECT MONTH(logged_in_at) AS month, COUNT(*) AS logins
            FROM user_login
            WHERE unit_id IN (:unitIds) AND YEAR(logged_in_at) = :year
            GROUP BY month
SQL;

        foreach ($this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAllKeyValue() as $month => $logins) {
            $months[(int) $month] = (int) $logins;
        }

        return $months;
    }

    private function hasLoginTable(): bool
    {
        return $this->loginTableExists ??= $this->connection
            ->createSchemaManager()
            ->tablesExist(['user_login']);
    }

    // ── Fáze 0: data the application already stored ───────────────────────

    private function preferenceAdoption(): PreferenceAdoption
    {
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS users,
                COALESCE(SUM(show_help), 0) AS show_help,
                COALESCE(SUM(extend_skautis_login), 0) AS extend_login,
                COALESCE(SUM(remember_skautis_role), 0) AS remember_role
            FROM user_preference
SQL;

        $row = $this->connection->executeQuery($sql)->fetchAssociative();

        if ($row === false) {
            return new PreferenceAdoption();
        }

        return new PreferenceAdoption(
            (int) $row['users'],
            (int) $row['show_help'],
            (int) $row['extend_login'],
            (int) $row['remember_role'],
        );
    }

    private function engagementSignals(UsageStatisticsQuery $query): EngagementSignals
    {
        $unitIds = $query->getUnitIds();

        if ($unitIds === []) {
            return new EngagementSignals();
        }

        $pairings = $this->fetchPairingTotals($unitIds, $query->getYear());

        return new EngagementSignals(
            pairingsTotal: $pairings['total'],
            pairingsAutomatic: $pairings['automatic'],
            paymentVisitUsers: $this->fetchPaymentVisitUsers($unitIds),
            activeAuthors: $this->fetchActiveAuthors($unitIds, $query->getYear()),
            reportDeviceTypes: $this->fetchReportDeviceTypes($unitIds, $query->getYear()),
        );
    }

    /**
     * @param int[] $unitIds
     *
     * @return array{total: int, automatic: int}
     */
    private function fetchPairingTotals(array $unitIds, int $year): array
    {
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN btp.pairing_mode = :automatic THEN 1 ELSE 0 END), 0) AS automatic
            FROM bank_transaction_pairing btp
            INNER JOIN bank_transaction bt ON bt.id = btp.bank_transaction_id
            INNER JOIN pa_bank_account ba ON ba.id = bt.bank_account_id
            WHERE btp.cancelled_at IS NULL
              AND ba.unit_id IN (:unitIds)
              AND YEAR(bt.date) = :year
SQL;

        $row = $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
            'year' => $year,
            'automatic' => BankTransactionPairingMode::AUTOMATIC->value,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
            'year' => ParameterType::INTEGER,
        ])->fetchAssociative();

        if ($row === false) {
            return ['total' => 0, 'automatic' => 0];
        }

        return ['total' => (int) $row['total'], 'automatic' => (int) $row['automatic']];
    }

    /**
     * Users with any remembered payment group. The table keeps at most three
     * rows per person, so this is a head-count of people who reached the
     * section — never a measure of how much they used it.
     *
     * @param int[] $unitIds
     */
    private function fetchPaymentVisitUsers(array $unitIds): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(DISTINCT v.user_id)
            FROM payment_group_visit v
            INNER JOIN pa_group_unit gu ON gu.group_id = v.group_id
            WHERE gu.unit_id IN (:unitIds)
SQL;

        return (int) $this->connection->executeQuery($sql, [
            'unitIds' => $unitIds,
        ], [
            'unitIds' => Connection::PARAM_INT_ARRAY,
        ])->fetchOne();
    }

    /**
     * People who changed something the domain log records. Narrower than "active
     * users" — reading a page leaves no trace here.
     *
     * @param int[] $unitIds
     */
    private function fetchActiveAuthors(array $unitIds, int $year): int
    {
        return (int) $this->connection->executeQuery(
            'SELECT COUNT(DISTINCT user_id) FROM log WHERE unit_id IN (:unitIds) AND YEAR(date) = :year',
            ['unitIds' => $unitIds, 'year' => $year],
            ['unitIds' => Connection::PARAM_INT_ARRAY, 'year' => ParameterType::INTEGER],
        )->fetchOne();
    }

    /**
     * Devices seen in technical reports. The only User-Agent the application
     * kept before login tracking existed — and a sample biased towards whoever
     * hit a bug, which is why it is reported next to the real figures, not
     * merged into them.
     *
     * @param int[] $unitIds
     *
     * @return array<string, int>
     */
    private function fetchReportDeviceTypes(array $unitIds, int $year): array
    {
        $userAgents = $this->connection->executeQuery(
            'SELECT user_agent FROM technical_error_report WHERE unit_id IN (:unitIds) AND YEAR(created_at) = :year',
            ['unitIds' => $unitIds, 'year' => $year],
            ['unitIds' => Connection::PARAM_INT_ARRAY, 'year' => ParameterType::INTEGER],
        )->fetchFirstColumn();

        $devices = [];
        foreach ($userAgents as $userAgent) {
            $type = $this->deviceClassifier->classify($userAgent !== null ? (string) $userAgent : null)->getType();

            if ($type === DeviceInfo::TYPE_BOT) {
                continue;
            }

            $devices[$type] = ($devices[$type] ?? 0) + 1;
        }

        arsort($devices);

        return $devices;
    }
}
