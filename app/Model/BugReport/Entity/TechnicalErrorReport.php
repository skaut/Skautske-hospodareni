<?php

declare(strict_types=1);

namespace App\Model\BugReport\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: \App\Model\BugReport\Repository\TechnicalErrorReportRepository::class)]
#[Table(name: 'technical_error_report')]
#[Index(name: 'technical_error_report_created_at_idx', columns: ['created_at'])]
#[Index(name: 'technical_error_report_resolved_at_idx', columns: ['resolved_at'])]
#[Index(name: 'technical_error_report_user_id_idx', columns: ['reporter_user_id'])]
class TechnicalErrorReport extends AbstractIdEntity
{
    public const RESOLUTION_FIXED = 'fixed';
    public const RESOLUTION_REJECTED = 'rejected';

    #[Column(type: Types::TEXT)]
    private string $description;

    #[Column(name: 'reported_url', type: Types::STRING, length: 2048, nullable: true)]
    private ?string $reportedUrl;

    #[Column(name: 'reporter_user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $reporterUserId;

    #[Column(name: 'reporter_display_name', type: Types::STRING, length: 255)]
    private string $reporterDisplayName;

    #[Column(name: 'reporter_email', type: Types::STRING, length: 255, nullable: true)]
    private ?string $reporterEmail;

    #[Column(name: 'role_id', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $roleId;

    #[Column(name: 'role_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $roleName;

    #[Column(name: 'unit_id', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $unitId;

    #[Column(name: 'unit_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $unitName;

    #[Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress;

    #[Column(name: 'user_agent', type: Types::TEXT, nullable: true)]
    private ?string $userAgent;

    #[Column(name: 'screenshot_path', type: Types::STRING, length: 255, nullable: true)]
    private ?string $screenshotPath;

    #[Column(name: 'screenshot_original_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $screenshotOriginalName;

    #[Column(name: 'screenshot_content_type', type: Types::STRING, length: 100, nullable: true)]
    private ?string $screenshotContentType;

    #[Column(name: 'screenshot_size', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $screenshotSize;

    #[Column(name: 'app_release', type: Types::STRING, length: 255)]
    private string $appRelease;

    /** @var array<string, mixed> */
    #[Column(type: Types::JSON)]
    private array $diagnostics;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[Column(name: 'notification_sent_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $notificationSentAt = null;

    #[Column(name: 'notification_error', type: Types::TEXT, nullable: true)]
    private ?string $notificationError = null;

    #[Column(name: 'resolved_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[Column(name: 'resolution_state', type: Types::STRING, length: 20, nullable: true)]
    private ?string $resolutionState = null;

    #[Column(name: 'resolution_message', type: Types::TEXT, nullable: true)]
    private ?string $resolutionMessage = null;

    #[Column(name: 'resolution_notification_sent_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $resolutionNotificationSentAt = null;

    #[Column(name: 'resolution_notification_error', type: Types::TEXT, nullable: true)]
    private ?string $resolutionNotificationError = null;

    #[Column(name: 'reply_error', type: Types::TEXT, nullable: true)]
    private ?string $replyError = null;

    /** @var Collection&iterable<TechnicalErrorReportReply> */
    #[OneToMany(mappedBy: 'report', targetEntity: TechnicalErrorReportReply::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[OrderBy(['sentAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $replies;

    /** @param array<string, mixed> $diagnostics */
    public function __construct(
        string $description,
        ?string $reportedUrl,
        int $reporterUserId,
        string $reporterDisplayName,
        ?string $reporterEmail,
        ?int $roleId,
        ?string $roleName,
        ?int $unitId,
        ?string $unitName,
        ?string $ipAddress,
        ?string $userAgent,
        string $appRelease,
        array $diagnostics,
        ?DateTimeImmutable $createdAt = null,
        ?string $screenshotPath = null,
        ?string $screenshotOriginalName = null,
        ?string $screenshotContentType = null,
        ?int $screenshotSize = null,
    ) {
        $this->description = $description;
        $this->reportedUrl = $reportedUrl;
        $this->reporterUserId = $reporterUserId;
        $this->reporterDisplayName = $reporterDisplayName;
        $this->reporterEmail = $reporterEmail;
        $this->roleId = $roleId;
        $this->roleName = $roleName;
        $this->unitId = $unitId;
        $this->unitName = $unitName;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->screenshotPath = $screenshotPath;
        $this->screenshotOriginalName = $screenshotOriginalName;
        $this->screenshotContentType = $screenshotContentType;
        $this->screenshotSize = $screenshotSize;
        $this->appRelease = $appRelease;
        $this->diagnostics = $diagnostics;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->replies = new ArrayCollection();
    }

    public function markNotificationSent(?DateTimeImmutable $sentAt = null): void
    {
        $this->notificationSentAt = $sentAt ?? new DateTimeImmutable();
        $this->notificationError = null;
    }

    public function markNotificationFailed(string $error): void
    {
        $this->notificationSentAt = null;
        $this->notificationError = $error;
    }

    public function markResolutionNotificationSent(?DateTimeImmutable $sentAt = null): void
    {
        $this->resolutionNotificationSentAt = $sentAt ?? new DateTimeImmutable();
        $this->resolutionNotificationError = null;
    }

    public function markResolutionNotificationFailed(string $error): void
    {
        $this->resolutionNotificationSentAt = null;
        $this->resolutionNotificationError = $error;
    }

    public function markReplySent(string $message, ?DateTimeImmutable $sentAt = null): void
    {
        $this->replies->add(new TechnicalErrorReportReply($this, $message, $sentAt));
        $this->replyError = null;
    }

    public function markReplyFailed(string $error): void
    {
        $this->replyError = $error;
    }

    public function resolveAsFixed(?string $message = null, ?DateTimeImmutable $resolvedAt = null): void
    {
        $this->resolve(self::RESOLUTION_FIXED, $message, $resolvedAt);
    }

    public function reject(string $message, ?DateTimeImmutable $resolvedAt = null): void
    {
        $this->resolve(self::RESOLUTION_REJECTED, $message, $resolvedAt);
    }

    private function resolve(string $state, ?string $message = null, ?DateTimeImmutable $resolvedAt = null): void
    {
        if ($this->resolvedAt !== null) {
            return;
        }

        $this->resolutionState = $state;
        $this->resolutionMessage = $message;
        $this->resolvedAt = $resolvedAt ?? new DateTimeImmutable();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getReportedUrl(): ?string
    {
        return $this->reportedUrl;
    }

    public function getReporterUserId(): int
    {
        return $this->reporterUserId;
    }

    public function getReporterDisplayName(): string
    {
        return $this->reporterDisplayName;
    }

    public function getReporterEmail(): ?string
    {
        return $this->reporterEmail;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function getUnitId(): ?int
    {
        return $this->unitId;
    }

    public function getUnitName(): ?string
    {
        return $this->unitName;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function hasScreenshot(): bool
    {
        return $this->screenshotPath !== null;
    }

    public function getScreenshotPath(): ?string
    {
        return $this->screenshotPath;
    }

    public function getScreenshotOriginalName(): ?string
    {
        return $this->screenshotOriginalName;
    }

    public function getScreenshotContentType(): ?string
    {
        return $this->screenshotContentType;
    }

    public function getScreenshotSize(): ?int
    {
        return $this->screenshotSize;
    }

    public function getAppRelease(): string
    {
        return $this->appRelease;
    }

    /** @return array<string, mixed> */
    public function getDiagnostics(): array
    {
        return $this->diagnostics;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNotificationSentAt(): ?DateTimeImmutable
    {
        return $this->notificationSentAt;
    }

    public function getNotificationError(): ?string
    {
        return $this->notificationError;
    }

    public function wasNotificationSent(): bool
    {
        return $this->notificationSentAt !== null;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getResolutionState(): ?string
    {
        return $this->resolutionState;
    }

    public function getResolutionMessage(): ?string
    {
        return $this->resolutionMessage;
    }

    public function getResolutionNotificationSentAt(): ?DateTimeImmutable
    {
        return $this->resolutionNotificationSentAt;
    }

    public function getResolutionNotificationError(): ?string
    {
        return $this->resolutionNotificationError;
    }

    public function wasResolutionNotificationSent(): bool
    {
        return $this->resolutionNotificationSentAt !== null;
    }

    public function isResolved(): bool
    {
        return $this->resolvedAt !== null;
    }

    public function getReplyError(): ?string
    {
        return $this->replyError;
    }

    /** @return list<TechnicalErrorReportReply> */
    public function getReplies(): array
    {
        return array_values($this->replies->toArray());
    }

    public function wasReplySent(): bool
    {
        return ! $this->replies->isEmpty();
    }
}
