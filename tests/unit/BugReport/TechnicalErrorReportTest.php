<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use Codeception\Test\Unit;
use DateTimeImmutable;

final class TechnicalErrorReportTest extends Unit
{
    public function testReportCanBeResolvedOnlyOnce(): void
    {
        $report = $this->createReport();
        $resolvedAt = new DateTimeImmutable('2026-06-19 11:00:00');

        self::assertFalse($report->isResolved());
        self::assertNull($report->getResolvedAt());
        self::assertSame('reporter@example.test', $report->getReporterEmail());

        $report->resolveAsFixed('Vyřešeno v nové verzi.', $resolvedAt);

        self::assertTrue($report->isResolved());
        self::assertSame($resolvedAt, $report->getResolvedAt());
        self::assertSame(TechnicalErrorReport::RESOLUTION_FIXED, $report->getResolutionState());
        self::assertSame('Vyřešeno v nové verzi.', $report->getResolutionMessage());

        $report->resolveAsFixed('Pozdější zpráva', new DateTimeImmutable('2026-06-19 12:00:00'));

        self::assertSame($resolvedAt, $report->getResolvedAt());
        self::assertSame(TechnicalErrorReport::RESOLUTION_FIXED, $report->getResolutionState());
        self::assertSame('Vyřešeno v nové verzi.', $report->getResolutionMessage());
    }

    public function testReportCanBeRejectedOnlyOnce(): void
    {
        $report = $this->createReport();
        $resolvedAt = new DateTimeImmutable('2026-06-19 11:00:00');

        $report->reject('Nejde o technickou chybu.', $resolvedAt);

        self::assertTrue($report->isResolved());
        self::assertSame($resolvedAt, $report->getResolvedAt());
        self::assertSame(TechnicalErrorReport::RESOLUTION_REJECTED, $report->getResolutionState());
        self::assertSame('Nejde o technickou chybu.', $report->getResolutionMessage());

        $report->resolveAsFixed('Pozdější zpráva', new DateTimeImmutable('2026-06-19 12:00:00'));

        self::assertSame($resolvedAt, $report->getResolvedAt());
        self::assertSame(TechnicalErrorReport::RESOLUTION_REJECTED, $report->getResolutionState());
        self::assertSame('Nejde o technickou chybu.', $report->getResolutionMessage());
    }

    public function testGitHubIssueStateCanBeStored(): void
    {
        $report = $this->createReport();
        $createdAt = new DateTimeImmutable('2026-07-14 12:00:00');

        self::assertFalse($report->hasGitHubIssue());

        $report->markGitHubIssueCreated(42, 'https://github.com/skaut/issues/42', $createdAt);

        self::assertTrue($report->hasGitHubIssue());
        self::assertSame(42, $report->getGitHubIssueNumber());
        self::assertSame('https://github.com/skaut/issues/42', $report->getGitHubIssueUrl());
        self::assertSame($createdAt, $report->getGitHubIssueCreatedAt());
        self::assertNull($report->getGitHubSyncError());

        $report->markGitHubSyncFailed('API error');

        self::assertSame('API error', $report->getGitHubSyncError());
    }

    public function testReplyCanStoreGitHubCommentState(): void
    {
        $report = $this->createReport();

        $reply = $report->markReplySent('Prosíme o doplnění.');
        $reply->markGitHubCommentCreated('https://github.com/skaut/issues/42#issuecomment-1');

        self::assertSame('https://github.com/skaut/issues/42#issuecomment-1', $reply->getGitHubCommentUrl());
        self::assertNull($reply->getGitHubCommentError());

        $reply->markGitHubCommentFailed('API error');

        self::assertNull($reply->getGitHubCommentUrl());
        self::assertSame('API error', $reply->getGitHubCommentError());
    }

    private function createReport(): TechnicalErrorReport
    {
        return new TechnicalErrorReport(
            'Testovací chyba',
            'https://example.test/chyba',
            123,
            'Testovací uživatel',
            'reporter@example.test',
            456,
            'Vedoucí',
            789,
            'Testovací jednotka',
            '127.0.0.1',
            'Test browser',
            'test-release',
            [],
        );
    }
}
