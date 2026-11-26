<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\QueryHandlers;

use App\Model\DTO\Stat\LoginStatistics;
use App\Model\DTO\Stat\PreferenceAdoption;
use App\Model\DTO\Stat\UsageOverview;
use App\Model\Stat\ReadModel\Queries\UsageStatisticsQuery;
use App\Model\User\Entity\UserLogin;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

use function array_slice;
use function sprintf;

/** The general overview of the usage page: logins of the whole system, without any split by units. */
final class UsageStatisticsQueryHandler
{
    /** Beyond this a browser row says more about the long tail than about the users. */
    private const MAX_BROWSER_ROWS = 8;

    private ?bool $loginTableExists = null;

    public function __construct(private Connection $connection)
    {
    }

    public function __invoke(UsageStatisticsQuery $query): UsageOverview
    {
        return new UsageOverview(
            $this->loginStatistics($query),
            $this->preferenceAdoption(),
        );
    }

    private function loginStatistics(UsageStatisticsQuery $query): LoginStatistics
    {
        if (! $this->hasLoginTable()) {
            return LoginStatistics::unavailable();
        }

        $month = $query->getMonth();
        $from = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $query->getYear(), $month ?? 1));
        $period = [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $from->modify($month === null ? '+1 year' : '+1 month')->format('Y-m-d H:i:s'),
        ];

        $totals = $this->fetchLoginTotals($period);
        $theme = $this->fetchThemeUsers($period);

        return new LoginStatistics(
            available: true,
            logins: $totals['logins'],
            users: $totals['users'],
            newUsers: $this->fetchNewUserCount($period),
            sessionMedianSeconds: $totals['median'],
            sessionP90Seconds: $totals['p90'],
            endedByLogout: $totals['endedByLogout'],
            deviceTypes: $this->fetchLoginBreakdown($period, 'device_type'),
            browsers: $this->fetchBrowserBreakdown($period),
            platforms: $this->fetchLoginBreakdown($period, 'platform'),
            weekMap: $this->fetchWeekMap($period),
            monthly: $this->fetchMonthlyLogins($period),
            themeUsers: $theme['users'],
            darkModeUsers: $theme['dark'],
        );
    }

    /**
     * @param array{from: string, to: string} $period
     *
     * @return array{logins: int, users: int, endedByLogout: int, median: int|null, p90: int|null}
     */
    private function fetchLoginTotals(array $period): array
    {
        // MySQL 8 has no PERCENTILE_CONT. Nearest-rank instead: order the sessions
        // and take the one at CEIL(p * n). PERCENT_RANK would look natural here
        // but reports the largest value *below* the percentile, so the slowest
        // session could never be the p90 no matter how far it stood out.
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS logins,
                COUNT(DISTINCT user_hash) AS users,
                COALESCE(SUM(CASE WHEN end_reason = :logout THEN 1 ELSE 0 END), 0) AS ended_by_logout,
                MAX(CASE WHEN position = median_position THEN active_seconds END) AS median_seconds,
                MAX(CASE WHEN position = p90_position THEN active_seconds END) AS p90_seconds
            FROM (
                SELECT
                    user_hash,
                    end_reason,
                    TIMESTAMPDIFF(SECOND, logged_in_at, last_seen_at) AS active_seconds,
                    ROW_NUMBER() OVER (ORDER BY TIMESTAMPDIFF(SECOND, logged_in_at, last_seen_at)) AS position,
                    CEIL(0.5 * COUNT(*) OVER ()) AS median_position,
                    CEIL(0.9 * COUNT(*) OVER ()) AS p90_position
                FROM user_login
                WHERE logged_in_at >= :from AND logged_in_at < :to
            ) ranked
SQL;

        $row = $this->connection->executeQuery($sql, $period + ['logout' => UserLogin::END_REASON_LOGOUT])->fetchAssociative();

        if ($row === false) {
            return ['logins' => 0, 'users' => 0, 'endedByLogout' => 0, 'median' => null, 'p90' => null];
        }

        return [
            'logins' => (int) $row['logins'],
            'users' => (int) $row['users'],
            'endedByLogout' => (int) $row['ended_by_logout'],
            'median' => $row['median_seconds'] !== null ? (int) $row['median_seconds'] : null,
            'p90' => $row['p90_seconds'] !== null ? (int) $row['p90_seconds'] : null,
        ];
    }

    /**
     * Users whose very first login falls inside the period.
     *
     * @param array{from: string, to: string} $period
     */
    private function fetchNewUserCount(array $period): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*)
            FROM (
                SELECT user_hash, MIN(logged_in_at) AS first_login
                FROM user_login
                GROUP BY user_hash
            ) first_seen
            WHERE first_seen.first_login >= :from AND first_seen.first_login < :to
SQL;

        return (int) $this->connection->executeQuery($sql, $period)->fetchOne();
    }

    /**
     * Users of the period by the last login that reported its theme.
     *
     * @param array{from: string, to: string} $period
     *
     * @return array{users: int, dark: int}
     */
    private function fetchThemeUsers(array $period): array
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) AS users, COALESCE(SUM(dark_mode), 0) AS dark
            FROM (
                SELECT
                    dark_mode,
                    ROW_NUMBER() OVER (PARTITION BY user_hash ORDER BY logged_in_at DESC, id DESC) AS position
                FROM user_login
                WHERE logged_in_at >= :from AND logged_in_at < :to AND dark_mode IS NOT NULL
            ) latest
            WHERE position = 1
SQL;

        $row = $this->connection->executeQuery($sql, $period)->fetchAssociative();

        if ($row === false) {
            return ['users' => 0, 'dark' => 0];
        }

        return ['users' => (int) $row['users'], 'dark' => (int) $row['dark']];
    }

    /**
     * @param array{from: string, to: string} $period
     *
     * @return array<string, int>
     */
    private function fetchLoginBreakdown(array $period, string $column): array
    {
        // $column is never user input — the call sites pass literals.
        $sql = sprintf(
            'SELECT %s AS bucket, COUNT(*) AS logins
                FROM user_login
                WHERE logged_in_at >= :from AND logged_in_at < :to
                GROUP BY %s
                ORDER BY logins DESC',
            $column,
            $column,
        );

        $breakdown = [];
        foreach ($this->connection->executeQuery($sql, $period)->fetchAllKeyValue() as $bucket => $logins) {
            $breakdown[(string) $bucket] = (int) $logins;
        }

        return $breakdown;
    }

    /**
     * @param array{from: string, to: string} $period
     *
     * @return array<string, int>
     */
    private function fetchBrowserBreakdown(array $period): array
    {
        $sql = <<<'SQL'
            SELECT
                CASE WHEN browser_version IS NULL THEN browser ELSE CONCAT(browser, ' ', browser_version) END AS bucket,
                COUNT(*) AS logins
            FROM user_login
            WHERE logged_in_at >= :from AND logged_in_at < :to
            GROUP BY bucket
            ORDER BY logins DESC
SQL;

        $breakdown = [];
        foreach ($this->connection->executeQuery($sql, $period)->fetchAllKeyValue() as $bucket => $logins) {
            $breakdown[(string) $bucket] = (int) $logins;
        }

        return array_slice($breakdown, 0, self::MAX_BROWSER_ROWS, true);
    }

    /**
     * @param array{from: string, to: string} $period
     *
     * @return array<int, array<int, int>>
     */
    private function fetchWeekMap(array $period): array
    {
        $sql = <<<'SQL'
            SELECT WEEKDAY(logged_in_at) AS weekday, HOUR(logged_in_at) AS hour_of_day, COUNT(*) AS logins
            FROM user_login
            WHERE logged_in_at >= :from AND logged_in_at < :to
            GROUP BY weekday, hour_of_day
SQL;

        $map = [];
        for ($day = 1; $day <= 7; ++$day) {
            for ($hour = 0; $hour < 24; ++$hour) {
                $map[$day][$hour] = 0;
            }
        }

        foreach ($this->connection->executeQuery($sql, $period)->fetchAllAssociative() as $row) {
            // WEEKDAY() counts Monday as 0; the map is 1-based so the template reads naturally.
            $map[(int) $row['weekday'] + 1][(int) $row['hour_of_day']] = (int) $row['logins'];
        }

        return $map;
    }

    /**
     * When in the year people actually work.
     *
     * @param array{from: string, to: string} $period
     *
     * @return array<int, int>
     */
    private function fetchMonthlyLogins(array $period): array
    {
        $months = [];
        for ($month = 1; $month <= 12; ++$month) {
            $months[$month] = 0;
        }

        $sql = <<<'SQL'
            SELECT MONTH(logged_in_at) AS month, COUNT(*) AS logins
            FROM user_login
            WHERE logged_in_at >= :from AND logged_in_at < :to
            GROUP BY month
SQL;

        foreach ($this->connection->executeQuery($sql, $period)->fetchAllKeyValue() as $month => $logins) {
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
}
