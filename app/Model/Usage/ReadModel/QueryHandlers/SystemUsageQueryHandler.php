<?php

declare(strict_types=1);

namespace App\Model\Usage\ReadModel\QueryHandlers;

use App\Model\DTO\Usage\MonthlySessions;
use App\Model\DTO\Usage\ScreenUsage;
use App\Model\DTO\Usage\SectionUsage;
use App\Model\DTO\Usage\SystemUsage;
use App\Model\DTO\Usage\UnitUsage;
use App\Model\Usage\ReadModel\Queries\SystemUsageQuery;
use App\Model\Usage\Repository\UsageRepository;
use App\Model\Usage\UsageScreen;
use App\Model\Usage\UsageSection;
use App\Model\Usage\UsageUnitCode;
use DateTimeImmutable;

use function array_keys;
use function count;
use function range;
use function sprintf;
use function strcmp;
use function usort;

final class SystemUsageQueryHandler
{
    public function __construct(private UsageRepository $repository)
    {
    }

    public function __invoke(SystemUsageQuery $query): SystemUsage
    {
        $month = $query->getMonth();
        $region = $query->getRegion();
        $district = $query->getDistrict();
        $from = new DateTimeImmutable(sprintf('%04d-%02d-01', $query->getYear(), $month ?? 1));
        $to = $month === null ? $from->modify('last day of december') : $from->modify('last day of this month');

        [$sessions, $sections] = $this->sections($this->repository->sumSectionsByMonth($from, $to, $region, $district));
        [$rows, $unitCount] = $this->rows($this->repository->sumUnitsByMonth($from, $to, $region, $district), $region, $district);

        return new SystemUsage(
            $month === null ? range(1, 12) : [$month],
            $sessions,
            $sections,
            $rows,
            $unitCount,
            $this->repository->findFirstDay(),
        );
    }

    /**
     * @param list<array{section: string, screen: string, month: int, sessions: int}> $data
     *
     * @return array{0: MonthlySessions, 1: list<SectionUsage>}
     */
    private function sections(array $data): array
    {
        $total = new MonthlySessions();
        $sections = [];
        $screens = [];

        foreach ($data as $row) {
            if ($row['section'] === '') {
                $total = $total->with($row['month'], $row['sessions']);

                continue;
            }

            if (UsageSection::tryFrom($row['section']) === null) {
                continue;
            }

            if ($row['screen'] === '') {
                $sections[$row['section']] = ($sections[$row['section']] ?? new MonthlySessions())->with($row['month'], $row['sessions']);

                continue;
            }

            $screens[$row['section']][$row['screen']] = ($screens[$row['section']][$row['screen']] ?? new MonthlySessions())
                ->with($row['month'], $row['sessions']);
        }

        $result = [];

        foreach ($sections as $section => $sessions) {
            $sectionScreens = [];

            foreach ($screens[$section] ?? [] as $presenterName => $screenSessions) {
                $sectionScreens[] = new ScreenUsage(UsageScreen::label($presenterName), $screenSessions);
            }

            usort($sectionScreens, static fn (ScreenUsage $a, ScreenUsage $b): int => [$b->sessions->getTotal(), $a->label] <=> [$a->sessions->getTotal(), $b->label]);

            $result[] = new SectionUsage(UsageSection::from($section), $sessions, $sectionScreens);
        }

        usort($result, static fn (SectionUsage $a, SectionUsage $b): int => $b->sessions->getTotal() <=> $a->sessions->getTotal()
            ?: strcmp($a->section->getLabel(), $b->section->getLabel()));

        return [$total, $result];
    }

    /**
     * The whole system shows a row per kraj (all of them, also without sessions), a kraj a row per okres and an
     * okres a row per středisko, always in the order of their numbers. A unit code is never finer than a středisko, the rest was cut off when counting.
     *
     * @param list<array{unitCode: string, section: string, month: int, sessions: int}> $data
     *
     * @return array{0: list<UnitUsage>, 1: int}
     */
    private function rows(array $data, ?string $region, ?string $district): array
    {
        $totals = [];
        $sections = [];
        $units = []; // keyed by the code, a numeric code becomes an integer key

        if ($region === null && $district === null) {
            foreach (array_keys(UsageUnitCode::REGIONS) as $code) {
                $totals[(string) $code] = new MonthlySessions();
            }
        }

        foreach ($data as $row) {
            $code = $this->rowCode($row['unitCode'], $region, $district);

            if ($row['section'] === '') {
                $totals[$code] = ($totals[$code] ?? new MonthlySessions())->with($row['month'], $row['sessions']);
                $units[$row['unitCode']] = true;

                continue;
            }

            $sections[$code][$row['section']] = ($sections[$code][$row['section']] ?? new MonthlySessions())
                ->with($row['month'], $row['sessions']);
        }

        $rows = [];

        foreach ($totals as $code => $sessions) {
            $rows[] = $this->row((string) $code, $sessions, $sections[$code] ?? [], $region, $district);
        }

        usort($rows, static fn (UnitUsage $a, UnitUsage $b): int => UsageUnitCode::compare($a->code, $b->code));

        return [$rows, count($units)];
    }

    private function rowCode(string $unitCode, ?string $region, ?string $district): string
    {
        return match (true) {
            $district !== null => $unitCode,
            $region !== null => UsageUnitCode::districtOf($unitCode) ?? UsageUnitCode::UNKNOWN,
            default => UsageUnitCode::regionOf($unitCode),
        };
    }

    /** @param array<string, MonthlySessions> $sections */
    private function row(string $code, MonthlySessions $sessions, array $sections, ?string $region, ?string $district): UnitUsage
    {
        if ($district !== null) {
            return new UnitUsage($code, UsageUnitCode::unitLabel($code), $sessions, $sections);
        }

        if ($region !== null) {
            $opens = UsageUnitCode::isDistrict($code) ? $code : null;

            return new UnitUsage($code, UsageUnitCode::districtLabel($code), $sessions, $sections, linkRegion: $opens === null ? null : $region, linkDistrict: $opens);
        }

        return new UnitUsage(
            $code,
            UsageUnitCode::regionLabel($code),
            $sessions,
            $sections,
            linkRegion: UsageUnitCode::isRegion($code) ? $code : null,
            linkDistrict: $code === UsageUnitCode::SPECIAL ? $code : null,
        );
    }
}
