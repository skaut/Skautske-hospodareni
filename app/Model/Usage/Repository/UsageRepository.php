<?php

declare(strict_types=1);

namespace App\Model\Usage\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\Usage\Entity\UsageSessionDaily;
use App\Model\Usage\UsageUnitCode;
use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

use function addcslashes;
use function array_map;
use function is_string;
use function substr;

/** @extends AbstractRepository<UsageSessionDaily> */
class UsageRepository extends AbstractRepository
{
    private const TABLE = 'usage_session_daily';

    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return UsageSessionDaily::class;
    }

    /** Lets counting stay dormant between a deploy and the migration instead of breaking every page in that window. */
    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->entityManager
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist([self::TABLE]);
    }

    /**
     * Adds one session to a counter. A single upsert on purpose: two sessions opening the same part of the
     * system in the same moment must not fight over a read-modify-write, the unique index decides which of them
     * creates the row.
     */
    public function increment(DateTimeImmutable $day, string $unitCode, string $section, string $screen, string $page): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO usage_session_daily (day, unit_code, section, screen, page, sessions) VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE sessions = sessions + 1',
            [$day->format('Y-m-d'), $unitCode, $section, $screen, $page],
            [ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING],
        );
    }

    /**
     * Sessions of all sections, of each section and of its screens per month. All sections have an empty
     * section, a section alone an empty screen. The whole system reads its own rows, where each login counts
     * once; a kraj or an okres sums the rows of its units.
     *
     * @param string|null $region   kraj, e.g. 620; null for the whole system
     * @param string|null $district okres, e.g. 621; null for the whole kraj
     *
     * @return list<array{section: string, screen: string, month: int, sessions: int}>
     */
    public function sumSectionsByMonth(DateTimeImmutable $from, DateTimeImmutable $to, ?string $region, ?string $district = null): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        [$regionSql, $parameters] = $region === null && $district === null
            ? [' AND unit_code = :all', ['all' => UsageUnitCode::ALL]]
            : $this->scopeCondition($region, $district);

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT section, screen, MONTH(day) AS month, SUM(sessions) AS sessions
                FROM usage_session_daily
                WHERE day BETWEEN :from AND :to AND page = \'\''.$regionSql.'
                GROUP BY section, screen, month',
            ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')] + $parameters,
        )->fetchAllAssociative();

        return array_map(static fn (array $row): array => [
            'section' => (string) $row['section'],
            'screen' => (string) $row['screen'],
            'month' => (int) $row['month'],
            'sessions' => (int) $row['sessions'],
        ], $rows);
    }

    /**
     * Sessions per unit code in all sections (empty section) and in each section, per month. A login that
     * worked under two units counts under both, so the rows may add up to more than the whole system.
     *
     * @param string|null $region   kraj, e.g. 620; null for the units of the whole system
     * @param string|null $district okres, e.g. 621; null for the whole kraj
     *
     * @return list<array{unitCode: string, section: string, month: int, sessions: int}>
     */
    public function sumUnitsByMonth(DateTimeImmutable $from, DateTimeImmutable $to, ?string $region, ?string $district = null): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        [$regionSql, $parameters] = $region === null && $district === null
            ? [' AND unit_code <> :all', ['all' => UsageUnitCode::ALL]]
            : $this->scopeCondition($region, $district);

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT unit_code, section, MONTH(day) AS month, SUM(sessions) AS sessions
                FROM usage_session_daily
                WHERE day BETWEEN :from AND :to AND screen = \'\' AND page = \'\''.$regionSql.'
                GROUP BY unit_code, section, month',
            ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')] + $parameters,
        )->fetchAllAssociative();

        return array_map(static fn (array $row): array => [
            'unitCode' => (string) $row['unit_code'],
            'section' => (string) $row['section'],
            'month' => (int) $row['month'],
            'sessions' => (int) $row['sessions'],
        ], $rows);
    }

    /**
     * Sessions per page over a closed range of days, most used first. Pages are counted only for the whole
     * system, so a login counts once on a page whatever role it used.
     *
     * @return array<string, int> page key => sessions
     */
    public function sumByPage(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT page, SUM(sessions) AS sessions
                FROM usage_session_daily
                WHERE day BETWEEN ? AND ? AND page <> \'\'
                GROUP BY page
                ORDER BY sessions DESC, page ASC',
            [$from->format('Y-m-d'), $to->format('Y-m-d')],
            [ParameterType::STRING, ParameterType::STRING],
        )->fetchAllKeyValue();

        return array_map('intval', $rows);
    }

    /** The first day ever counted, so a report can say how far back it can see. */
    public function findFirstDay(): ?DateTimeImmutable
    {
        if (! $this->isStorageAvailable()) {
            return null;
        }

        $day = $this->entityManager->getConnection()
            ->executeQuery('SELECT MIN(day) FROM usage_session_daily')
            ->fetchOne();

        if (! is_string($day)) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $day);

        return $parsed === false ? null : $parsed;
    }

    /**
     * Units of an okres are the okres itself and its střediska. Units of a kraj are all okresy that start
     * with the first two places of the kraj, with their střediska.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function scopeCondition(?string $region, ?string $district): array
    {
        if ($district !== null) {
            return [
                ' AND (unit_code = :district OR unit_code LIKE :districtUnits)',
                ['district' => $district, 'districtUnits' => addcslashes($district, '%_\\').'.%'],
            ];
        }

        $prefix = addcslashes(substr((string) $region, 0, 2), '%_\\');

        return [
            ' AND (unit_code LIKE :regionDistricts OR unit_code LIKE :regionUnits)',
            ['regionDistricts' => $prefix.'_', 'regionUnits' => $prefix.'_.%'],
        ];
    }
}
