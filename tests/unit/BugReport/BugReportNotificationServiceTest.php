<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use Codeception\Test\Unit;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Utils\FileSystem as FileSystemUtil;
use ReflectionProperty;

use function dirname;
use function file_put_contents;

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

    public function testNotificationAttachesScreenshot(): void
    {
        $mailer = new class implements Mailer {
            public ?Message $message = null;

            public function send(Message $mail): void
            {
                $this->message = $mail;
            }
        };
        $directory = codecept_output_dir('bug-report-screenshots');
        $relativePath = BugReportScreenshotStorage::DIRECTORY.'/screenshot.jpg';
        FileSystemUtil::createDir(dirname($directory.'/'.$relativePath));
        file_put_contents($directory.'/'.$relativePath, 'jpeg-content');

        $service = new BugReportNotificationService(
            $mailer,
            false,
            false,
            ['admin@example.test'],
            'https://h.skauting.cz',
            new BugReportScreenshotStorage(
                new Filesystem(new LocalFilesystemAdapter($directory)),
                $directory,
            ),
        );

        $service->notify($this->createReport(
            'jana.kvapilova@example.test',
            $relativePath,
            'screenshot.jpg',
            'image/jpeg',
            12,
        ));

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertCount(1, $mailer->message->getAttachments());
        self::assertStringContainsString('filename="screenshot.jpg"', (string) $mailer->message->getAttachments()[0]->getHeader('Content-Disposition'));
        self::assertSame('jpeg-content', $mailer->message->getAttachments()[0]->getBody());
        self::assertStringContainsString('Screenshot: screenshot.jpg', (string) $mailer->message->getBody());
    }

    public function testResolutionNotificationIsSentToReporter(): void
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

        $service->notifyResolution(
            $this->createReport('jana.kvapilova@example.test'),
            'Chyba je opravena v nové produkční verzi.',
        );

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertSame(['jana.kvapilova@example.test' => 'Jana Kvapilová'], $mailer->message->getHeader('To'));
        self::assertStringContainsString('Hlášení #123 bylo zpracováno', (string) $mailer->message->getSubject());
        self::assertStringContainsString('Chyba je opravena v nové produkční verzi.', (string) $mailer->message->getBody());
        self::assertStringContainsString('Požadavek byl zpracován a oprava je upravena v nové verzi aplikace.', (string) $mailer->message->getBody());
    }

    public function testRejectionNotificationIsSentToReporter(): void
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

        $service->notifyRejection(
            $this->createReport('jana.kvapilova@example.test'),
            'Nejde o technickou chybu aplikace.',
        );

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertSame(['jana.kvapilova@example.test' => 'Jana Kvapilová'], $mailer->message->getHeader('To'));
        self::assertStringContainsString('Hlášení #123 bylo zamítnuto', (string) $mailer->message->getSubject());
        self::assertStringContainsString('Nejde o technickou chybu aplikace.', (string) $mailer->message->getBody());
        self::assertStringContainsString('byl uzavřen bez opravy', (string) $mailer->message->getBody());
    }

    public function testReplyNotificationUsesConfiguredErrorEmailAsSender(): void
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

        $service->notifyReply(
            $this->createReport('jana.kvapilova@example.test'),
            'Prosíme o doplnění přesného času chyby.',
        );

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertSame(['admin@example.test' => 'Skautské hospodaření'], $mailer->message->getHeader('From'));
        self::assertSame(['jana.kvapilova@example.test' => 'Jana Kvapilová'], $mailer->message->getHeader('To'));
        self::assertStringContainsString('Odpověď k hlášení #123', (string) $mailer->message->getSubject());
        self::assertStringContainsString('Prosíme o doplnění přesného času chyby.', (string) $mailer->message->getBody());
    }

    private function createReport(
        ?string $reporterEmail,
        ?string $screenshotPath = null,
        ?string $screenshotOriginalName = null,
        ?string $screenshotContentType = null,
        ?int $screenshotSize = null,
    ): TechnicalErrorReport {
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
            screenshotPath: $screenshotPath,
            screenshotOriginalName: $screenshotOriginalName,
            screenshotContentType: $screenshotContentType,
            screenshotSize: $screenshotSize,
        );

        $id = new ReflectionProperty($report, 'id');
        $id->setAccessible(true);
        $id->setValue($report, 123);

        return $report;
    }
}
