<?php

declare(strict_types=1);

namespace App\Model\Announcement\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;
use Nette\Utils\Strings;

#[Entity(repositoryClass: \App\Model\Announcement\Repository\AnnouncementRepository::class)]
#[Table(name: 'announcement')]
#[Index(name: 'announcement_visible_published_idx', columns: ['hidden', 'published_at', 'expires_at'])]
#[Index(name: 'IDX_ANNOUNCEMENT_CATEGORY', columns: ['category_code'])]
class Announcement extends AbstractIdEntity
{
    public const MAX_MESSAGE_LENGTH = 600;

    #[Column(type: Types::STRING, length: 255)]
    private string $title;

    #[Column(name: 'message', type: Types::STRING, length: self::MAX_MESSAGE_LENGTH)]
    private string $message;

    #[ManyToOne(targetEntity: AnnouncementCategory::class)]
    #[JoinColumn(name: 'category_code', referencedColumnName: 'code', nullable: false, onDelete: 'RESTRICT')]
    private AnnouncementCategory $category;

    #[Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $publishedAt;

    #[Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $expiresAt;

    #[Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $hidden = false;

    public function __construct(
        string $title,
        string $message,
        AnnouncementCategory $category,
        ?DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $publishedAt = null,
    ) {
        $this->update($title, $message, $category, $expiresAt);
        $this->publishedAt = $publishedAt ?? new DateTimeImmutable();
    }

    public function update(string $title, string $message, AnnouncementCategory $category, ?DateTimeImmutable $expiresAt): void
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException('Announcement title must not be empty.');
        }
        if (Strings::length($title) > 255) {
            throw new InvalidArgumentException('Announcement title must not exceed 255 characters.');
        }
        if (trim($message) === '') {
            throw new InvalidArgumentException('Announcement message must not be empty.');
        }
        if (Strings::length($message) > self::MAX_MESSAGE_LENGTH) {
            throw new InvalidArgumentException('Announcement message must not exceed 600 characters.');
        }
        $this->title = $title;
        $this->message = $message;
        $this->category = $category;
        $this->expiresAt = $expiresAt;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCategory(): AnnouncementCategory
    {
        return $this->category;
    }

    public function getPublishedAt(): DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
