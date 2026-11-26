<?php

declare(strict_types=1);

namespace App\Model\Usage\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Usage\Repository\UsageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * How many sessions used one part of the system on one day, in the whole system or under one unit.
 *
 * A session is one login on one day. The empty string marks the level above:
 * - unit code empty: the whole system. A login counts there once in total, once in each section, once on each
 *   screen and once on each page (`Presenter:action`, used by the page list of Admin:Help), whatever role it
 *   switches to.
 * - unit code filled: the unit of a role. A login counts once under each unit it worked under, in total, in each
 *   section and on each screen. Kraje and units are summed from these rows.
 * - section empty: all sections, screen empty: the whole section, page empty: the whole screen.
 *
 * The point of the table is what is not in it: no user, no login and no address. The unit is cut to the first
 * two parts of its registration number, so it never points to a troop.
 *
 * Rows are written by an upsert in {@see UsageRepository::increment()}, never through the entity manager.
 */
#[Entity(repositoryClass: UsageRepository::class)]
#[Table(name: 'usage_session_daily')]
#[UniqueConstraint(name: 'usage_session_daily_uniq', columns: ['day', 'unit_code', 'section', 'screen', 'page'])]
class UsageSessionDaily extends AbstractIdEntity
{
    public const UNIT_CODE_MAX_LENGTH = 32;
    public const SECTION_MAX_LENGTH = 32;
    public const SCREEN_MAX_LENGTH = 64;

    /** The same limit `page_help` uses, so both address a page by the same key. */
    public const PAGE_MAX_LENGTH = 191;

    #[Column(name: 'day', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $day;

    #[Column(name: 'unit_code', type: Types::STRING, length: self::UNIT_CODE_MAX_LENGTH)]
    private string $unitCode;

    #[Column(name: 'section', type: Types::STRING, length: self::SECTION_MAX_LENGTH)]
    private string $section;

    #[Column(name: 'screen', type: Types::STRING, length: self::SCREEN_MAX_LENGTH)]
    private string $screen;

    #[Column(name: 'page', type: Types::STRING, length: self::PAGE_MAX_LENGTH)]
    private string $page;

    #[Column(name: 'sessions', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $sessions;

    public function __construct(
        DateTimeImmutable $day,
        string $unitCode,
        string $section,
        string $screen,
        string $page,
        int $sessions = 1,
    ) {
        $this->day = $day;
        $this->unitCode = $unitCode;
        $this->section = $section;
        $this->screen = $screen;
        $this->page = $page;
        $this->sessions = $sessions;
    }

    public function getDay(): DateTimeImmutable
    {
        return $this->day;
    }

    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getScreen(): string
    {
        return $this->screen;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function getSessions(): int
    {
        return $this->sessions;
    }
}
