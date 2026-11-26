<?php

declare(strict_types=1);

namespace App\Model\Usage\Services;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Usage\Entity\UsageSessionDaily;
use App\Model\Usage\Repository\UsageRepository;
use App\Model\Usage\UsageSection;
use App\Model\Usage\UsageUnitCode;
use DateTimeImmutable;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Psr\Log\LoggerInterface;
use Skautis\Skautis;
use Throwable;

use function hash;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function strlen;

/**
 * Counts sessions for the usage statistics in the administration.
 *
 * A login counts once per day in the whole system, in a section, on a screen and on a page, however many
 * requests it makes there and whatever role it switches to. Next to that it counts once per day under the unit
 * of each role it used, in the whole system, in a section and on a screen, for the overview by kraje and units.
 * What was already counted is kept in the server session, so nothing is stored in the browser and the database
 * gets only sums without the user.
 *
 * Nothing here may ever break a request: every error is swallowed and logged. A statistic is not a reason for
 * someone to lose a page.
 */
final class UsageTracker
{
    private const SESSION_SECTION = 'usageTracking';

    public function __construct(
        private Skautis $skautis,
        private IUnitRepository $units,
        private UsageRepository $repository,
        private Session $session,
        private LoggerInterface $logger,
    ) {
    }

    /** @param string $presenterName full name, e.g. Payments:GroupList */
    public function record(string $presenterName, string $action): void
    {
        try {
            $section = UsageSection::fromPresenterName($presenterName);
            $page = $presenterName.':'.$action;

            if (
                $section === null
                || strlen($presenterName) > UsageSessionDaily::SCREEN_MAX_LENGTH
                || strlen($page) > UsageSessionDaily::PAGE_MAX_LENGTH
            ) {
                return;
            }

            $loginId = $this->skautis->getUser()->getLoginId();

            if (! is_string($loginId) || $loginId === '') {
                return;
            }

            $today = new DateTimeImmutable('today');
            $state = $this->sessionState($loginId, $today);
            $unitCode = $this->resolveUnitCode($state, $this->skautis->getUser()->getUnitId());

            /** @var array<string, true> $counted */
            $counted = $state->get('counted');
            $pending = [];

            foreach ($this->levels($unitCode, $section, $presenterName, $page) as $level) {
                $key = implode("\n", $level);

                if (! isset($counted[$key])) {
                    $pending[$key] = $level;
                }
            }

            if ($pending === [] || ! $this->repository->isStorageAvailable()) {
                return;
            }

            foreach ($pending as $key => [$levelUnitCode, $sectionValue, $screen, $pageKey]) {
                $this->repository->increment($today, $levelUnitCode, $sectionValue, $screen, $pageKey);
                $counted[$key] = true;
                $state->set('counted', $counted);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se zaznamenat využití systému.', ['exception' => $e]);
        }
    }

    /**
     * The counters one request belongs to: the whole system (an empty unit code) down to the page, and the unit
     * of the role down to the screen. An empty string marks the level above.
     *
     * @return list<array{string, string, string, string}> unit code, section, screen, page
     */
    private function levels(string $unitCode, UsageSection $section, string $presenterName, string $page): array
    {
        return [
            [UsageUnitCode::ALL, '', '', ''],
            [UsageUnitCode::ALL, $section->value, '', ''],
            [UsageUnitCode::ALL, $section->value, $presenterName, ''],
            [UsageUnitCode::ALL, $section->value, $presenterName, $page],
            [$unitCode, '', '', ''],
            [$unitCode, $section->value, '', ''],
            [$unitCode, $section->value, $presenterName, ''],
        ];
    }

    /**
     * What this login already counted today. A new login (another skautIS token) or a new day starts again.
     * The token itself is not kept, only its hash.
     */
    private function sessionState(string $loginId, DateTimeImmutable $today): SessionSection
    {
        $state = $this->session->getSection(self::SESSION_SECTION);
        $login = hash('sha256', $loginId);
        $day = $today->format('Y-m-d');

        if ($state->get('login') !== $login) {
            $state->set('login', $login);
            $state->set('units', []);
            $state->set('day', null);
        }

        if ($state->get('day') !== $day || ! is_array($state->get('counted'))) {
            $state->set('day', $day);
            $state->set('counted', []);
        }

        return $state;
    }

    /**
     * The unit is looked up once per login and role. A role without a unit, or one skautIS does not tell us
     * about, counts as unknown; the failed lookup is not repeated on every page of the login.
     */
    private function resolveUnitCode(SessionSection $state, mixed $unitId): string
    {
        if (! is_int($unitId) || $unitId <= 0) {
            return UsageUnitCode::UNKNOWN;
        }

        $codes = $state->get('units');
        $codes = is_array($codes) ? $codes : [];

        if (isset($codes[$unitId]) && is_string($codes[$unitId])) {
            return $codes[$unitId];
        }

        try {
            $unit = $this->units->find($unitId);
            $code = UsageUnitCode::fromUnit($unit->getRegistrationNumber(), $unit->getType());
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se zjistit jednotku role pro statistiky využití.', ['exception' => $e]);
            $code = UsageUnitCode::UNKNOWN;
        }

        $codes[$unitId] = strlen($code) > UsageSessionDaily::UNIT_CODE_MAX_LENGTH ? UsageUnitCode::UNKNOWN : $code;
        $state->set('units', $codes);

        return $codes[$unitId];
    }
}
