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

        $report->resolve($resolvedAt);

        self::assertTrue($report->isResolved());
        self::assertSame($resolvedAt, $report->getResolvedAt());

        $report->resolve(new DateTimeImmutable('2026-06-19 12:00:00'));

        self::assertSame($resolvedAt, $report->getResolvedAt());
    }

    private function createReport(): TechnicalErrorReport
    {
        return new TechnicalErrorReport(
            'Testovací chyba',
            'https://example.test/chyba',
            123,
            'Testovací uživatel',
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
