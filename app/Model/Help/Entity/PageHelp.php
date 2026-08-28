<?php

declare(strict_types=1);

namespace App\Model\Help\Entity;

use App\Model\Help\HelpSection;
use App\Model\Help\Repository\PageHelpRepository;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

use function array_map;
use function array_values;
use function count;

/**
 * Contextual help shown in the sidebar of one page, editable in the administration.
 *
 * A page is addressed by its presenter and action, for example
 * `Travel:Contract:default`, so adding help to a page needs no template change.
 */
#[Entity(repositoryClass: PageHelpRepository::class)]
#[Table(name: 'page_help')]
#[UniqueConstraint(name: 'page_help_page_key_uniq', columns: ['page_key'])]
class PageHelp extends AbstractIdEntity
{
    public const PAGE_KEY_MAX_LENGTH = 191;

    public const LEAD_MAX_LENGTH = 500;

    #[Column(name: 'page_key', type: Types::STRING, length: self::PAGE_KEY_MAX_LENGTH)]
    private string $pageKey;

    /** Overrides the lead strip under the page title; null keeps the template wording. */
    #[Column(name: 'lead_text', type: Types::STRING, length: self::LEAD_MAX_LENGTH, nullable: true)]
    private ?string $lead;

    /** @var array<int, array{heading: string, text: string, items: string[]}> */
    #[Column(name: 'sections', type: Types::JSON)]
    private array $sections;

    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    #[Column(name: 'updated_by_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $updatedByName;

    /** @param HelpSection[] $sections */
    public function __construct(
        string $pageKey,
        ?string $lead,
        array $sections,
        DateTimeImmutable $updatedAt,
        ?string $updatedByName,
    ) {
        $this->pageKey = $pageKey;
        $this->update($lead, $sections, $updatedAt, $updatedByName);
    }

    /** @param HelpSection[] $sections */
    public function update(?string $lead, array $sections, DateTimeImmutable $updatedAt, ?string $updatedByName): void
    {
        $this->lead = $lead;
        $this->sections = array_values(array_map(
            static fn (HelpSection $section): array => $section->toArray(),
            $sections,
        ));
        $this->updatedAt = $updatedAt;
        $this->updatedByName = $updatedByName;
    }

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function getLead(): ?string
    {
        return $this->lead;
    }

    public function hasContent(): bool
    {
        return $this->lead !== null || $this->sections !== [];
    }

    /** @return HelpSection[] */
    public function getSections(): array
    {
        return array_map(
            static fn (array $section): HelpSection => HelpSection::fromArray($section),
            $this->sections,
        );
    }

    public function getSectionCount(): int
    {
        return count($this->sections);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUpdatedByName(): ?string
    {
        return $this->updatedByName;
    }
}
