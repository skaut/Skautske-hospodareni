<?php

declare(strict_types=1);

namespace App\Model\BugReport\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'technical_error_report_reply')]
#[Index(name: 'technical_error_report_reply_report_sent_at_idx', columns: ['report_id', 'sent_at'])]
class TechnicalErrorReportReply extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: TechnicalErrorReport::class, inversedBy: 'replies')]
    #[JoinColumn(name: 'report_id', nullable: false, onDelete: 'CASCADE')]
    private TechnicalErrorReport $report;

    #[Column(type: Types::TEXT)]
    private string $message;

    #[Column(name: 'sent_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $sentAt;

    #[Column(name: 'github_comment_url', type: Types::STRING, length: 2048, nullable: true)]
    private ?string $githubCommentUrl = null;

    #[Column(name: 'github_comment_error', type: Types::TEXT, nullable: true)]
    private ?string $githubCommentError = null;

    public function __construct(TechnicalErrorReport $report, string $message, ?DateTimeImmutable $sentAt = null)
    {
        $this->report = $report;
        $this->message = $message;
        $this->sentAt = $sentAt ?? new DateTimeImmutable();
    }

    public function getReport(): TechnicalErrorReport
    {
        return $this->report;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function markGitHubCommentCreated(string $commentUrl): void
    {
        $this->githubCommentUrl = $commentUrl;
        $this->githubCommentError = null;
    }

    public function markGitHubCommentFailed(string $error): void
    {
        $this->githubCommentUrl = null;
        $this->githubCommentError = $error;
    }

    public function getGitHubCommentUrl(): ?string
    {
        return $this->githubCommentUrl;
    }

    public function getGitHubCommentError(): ?string
    {
        return $this->githubCommentError;
    }
}
