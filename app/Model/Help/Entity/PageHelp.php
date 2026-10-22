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
use InvalidArgumentException;

use function array_map;
use function array_values;
use function count;
use function parse_str;
use function parse_url;
use function preg_match;
use function strtolower;
use function trim;

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

    public const YOUTUBE_TITLE_MAX_LENGTH = 255;

    public const YOUTUBE_URL_MAX_LENGTH = 2048;

    #[Column(name: 'page_key', type: Types::STRING, length: self::PAGE_KEY_MAX_LENGTH)]
    private string $pageKey;

    /** Overrides the lead strip under the page title; null keeps the template wording. */
    #[Column(name: 'lead_text', type: Types::STRING, length: self::LEAD_MAX_LENGTH, nullable: true)]
    private ?string $lead;

    /** A canonical, tracking-free YouTube watch URL. */
    #[Column(name: 'youtube_url', type: Types::STRING, length: self::YOUTUBE_URL_MAX_LENGTH, nullable: true)]
    private ?string $youtubeUrl;

    #[Column(name: 'youtube_title', type: Types::STRING, length: self::YOUTUBE_TITLE_MAX_LENGTH, nullable: true)]
    private ?string $youtubeTitle;

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
        ?string $youtubeTitle,
        ?string $youtubeUrl,
        DateTimeImmutable $updatedAt,
        ?string $updatedByName,
    ) {
        $this->pageKey = $pageKey;
        $this->update($lead, $sections, $youtubeTitle, $youtubeUrl, $updatedAt, $updatedByName);
    }

    /** @param HelpSection[] $sections */
    public function update(
        ?string $lead,
        array $sections,
        ?string $youtubeTitle,
        ?string $youtubeUrl,
        DateTimeImmutable $updatedAt,
        ?string $updatedByName,
    ): void {
        [$youtubeTitle, $youtubeUrl] = $this->normalizeYouTubeVideo($youtubeTitle, $youtubeUrl);

        $this->lead = $lead;
        $this->sections = array_values(array_map(
            static fn (HelpSection $section): array => $section->toArray(),
            $sections,
        ));
        $this->youtubeTitle = $youtubeTitle;
        $this->youtubeUrl = $youtubeUrl;
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

    public function getYoutubeTitle(): ?string
    {
        return $this->youtubeTitle;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function hasContent(): bool
    {
        return $this->lead !== null || $this->youtubeUrl !== null || $this->sections !== [];
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

    /**
     * Keeps only a stable video identifier. Share and watch URLs routinely contain
     * attribution parameters, and no query parameter other than the identifier is
     * needed to open the video itself.
     *
     * @return array{?string, ?string}
     */
    private function normalizeYouTubeVideo(?string $title, ?string $url): array
    {
        $title = $title === null ? null : trim($title);
        $url = $url === null ? null : trim($url);
        $title = $title === '' ? null : $title;
        $url = $url === '' ? null : $url;

        if ($title === null && $url === null) {
            return [null, null];
        }

        if ($title === null) {
            throw new InvalidArgumentException('Vyplňte název videa YouTube.');
        }

        if ($url === null) {
            throw new InvalidArgumentException('Vyplňte odkaz na video YouTube.');
        }

        $parts = parse_url($url);
        $host = is_array($parts) && isset($parts['host']) ? strtolower($parts['host']) : '';
        $path = is_array($parts) ? trim((string) ($parts['path'] ?? ''), '/') : '';
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = $path;
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $videoId = isset($query['v']) && is_string($query['v']) ? $query['v'] : null;

            if ($videoId === null && preg_match('~^(?:embed|live|shorts)/([^/]+)$~', $path, $matches) === 1) {
                $videoId = $matches[1];
            }
        }

        if (! is_string($videoId) || preg_match('~^[A-Za-z0-9_-]{11}$~', $videoId) !== 1) {
            throw new InvalidArgumentException('Zadejte odkaz na konkrétní video YouTube.');
        }

        return [$title, 'https://www.youtube.com/watch?v='.$videoId];
    }
}
