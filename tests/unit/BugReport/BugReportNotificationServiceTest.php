<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use Codeception\Test\Unit;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use ReflectionProperty;

final class BugReportNotificationServiceTest extends Unit
{
    public function testNotificationUsesReporterEmailAsReplyTo(): void
    {
        $mailer = new class implements Mailer {
            public ?Message $message = null;

            public function send(Message $mail): void
            {
                $this->message = $mail;
            }
        };

        $service = new BugReportNotificationService(
            $mailer,
            false,
            false,
            ['admin@example.test'],
            'https://h.skauting.cz',
        );

        $service->notify($this->createReport('jana.kvapilova@example.test'));

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertSame(
            ['jana.kvapilova@example.test' => 'Jana Kvapilová'],
            $mailer->message->getHeader('Reply-To'),
        );
        self::assertStringContainsString(
            'E-mail uživatele: jana.kvapilova@example.test',
            (string) $mailer->message->getBody(),
        );
    }

    public function testNotificationOmitsReplyToWithoutReporterEmail(): void
    {
        $mailer = new class implements Mailer {
            public ?Message $message = null;

            public function send(Message $mail): void
            {
                $this->message = $mail;
            }
        };

        $service = new BugReportNotificationService(
            $mailer,
            false,
            false,
            ['admin@example.test'],
            'https://h.skauting.cz',
        );

        $service->notify($this->createReport(null));

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertNull($mailer->message->getHeader('Reply-To'));
        self::assertStringContainsString('E-mail uživatele: -', (string) $mailer->message->getBody());
    }

    private function createReport(?string $reporterEmail): TechnicalErrorReport
    {
        $report = new TechnicalErrorReport(
            'Nefunguje export.',
            'https://h.skauting.cz/akce/123',
            1882,
            'Jana Kvapilová',
            $reporterEmail,
            117123,
            'Středisko: správce akcí - 623.21',
            23378,
            'středisko Pozořice',
            '127.0.0.1',
            'Test browser',
            'test-release',
            [],
        );

        $id = new ReflectionProperty($report, 'id');
        $id->setAccessible(true);
        $id->setValue($report, 123);

        return $report;
    }
}
