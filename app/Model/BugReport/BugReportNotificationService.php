<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use App\Model\Mail\SystemEmailTemplate;
use App\Model\Mail\SystemMailer;
use Nette\Utils\Json;
use RuntimeException;

use function array_filter;
use function array_values;
use function implode;

final class BugReportNotificationService
{
    /** @param string[] $recipients */
    public function __construct(
        private SystemMailer $systemMailer,
        private array $recipients,
        private string $appBaseUrl,
        private ?BugReportScreenshotStorage $screenshotStorage = null,
    ) {
    }

    public function notify(TechnicalErrorReport $report): void
    {
        $recipients = array_filter($this->recipients);
        if ($recipients === []) {
            throw new RuntimeException('No technical error report recipient is configured.');
        }

        $this->systemMailer->send(
            SystemEmailTemplate::BUG_REPORT_NOTIFICATION,
            $this->createNotificationParameters($report),
            $this->recipientsToMap($recipients),
            replyTo: $report->getReporterEmail() !== null
                ? ['email' => $report->getReporterEmail(), 'name' => $report->getReporterDisplayName()]
                : null,
            attachments: $this->createAttachments($report),
        );
    }

    public function notifyResolution(TechnicalErrorReport $report, string $messageText): void
    {
        $this->notifyClosure(
            $report,
            SystemEmailTemplate::BUG_REPORT_RESOLUTION,
            ['messageText' => $messageText],
        );
    }

    public function notifyRejection(TechnicalErrorReport $report, string $messageText): void
    {
        $this->notifyClosure(
            $report,
            SystemEmailTemplate::BUG_REPORT_REJECTION,
            ['messageText' => $messageText],
        );
    }

    public function notifyReply(TechnicalErrorReport $report, string $messageText): void
    {
        $recipient = $report->getReporterEmail();
        if ($recipient === null) {
            throw new RuntimeException('Technical error report has no reporter email.');
        }

        $sender = $this->getReplySender();
        $this->systemMailer->send(
            SystemEmailTemplate::BUG_REPORT_REPLY,
            $this->createReporterNotificationParameters($report, ['messageText' => $messageText]),
            [$recipient => $report->getReporterDisplayName()],
            fromEmail: $sender,
        );
    }

    /** @param array<string, mixed> $parameters */
    private function notifyClosure(TechnicalErrorReport $report, SystemEmailTemplate $template, array $parameters): void
    {
        $recipient = $report->getReporterEmail();
        if ($recipient === null) {
            throw new RuntimeException('Technical error report has no reporter email.');
        }

        $this->systemMailer->send(
            $template,
            $this->createReporterNotificationParameters($report, $parameters),
            [$recipient => $report->getReporterDisplayName()],
        );
    }

    /** @return array<string, mixed> */
    private function createNotificationParameters(TechnicalErrorReport $report): array
    {
        $role = array_filter([
            $report->getRoleName(),
            $report->getRoleId() !== null ? 'ID '.$report->getRoleId() : null,
        ]);
        $unit = array_filter([
            $report->getUnitName(),
            $report->getUnitId() !== null ? 'ID '.$report->getUnitId() : null,
        ]);

        return $this->createReporterNotificationParameters($report, [
            'adminUrl' => $this->appBaseUrl.'/admin/hlaseni-chyb/'.$report->getId(),
            'createdAt' => $report->getCreatedAt()->format('Y-m-d H:i:s P'),
            'reporterUserId' => $report->getReporterUserId(),
            'reporterEmail' => $report->getReporterEmail(),
            'roleLabel' => $role !== [] ? implode(', ', $role) : '-',
            'unitLabel' => $unit !== [] ? implode(', ', $unit) : '-',
            'ipAddress' => $report->getIpAddress(),
            'appRelease' => $report->getAppRelease(),
            'userAgent' => $report->getUserAgent(),
            'screenshotLabel' => $report->hasScreenshot() ? ($report->getScreenshotOriginalName() ?? 'přiložen') : '-',
            'diagnosticsJson' => Json::encode($report->getDiagnostics(), JSON_PRETTY_PRINT),
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function createReporterNotificationParameters(TechnicalErrorReport $report, array $parameters): array
    {
        return $parameters + [
            'reportId' => $report->getId(),
            'reporterDisplayName' => $report->getReporterDisplayName(),
            'description' => $report->getDescription(),
            'reportedUrl' => $report->getReportedUrl(),
        ];
    }

    /** @return list<array{name: string, contents: string, contentType: string|null}> */
    private function createAttachments(TechnicalErrorReport $report): array
    {
        if (! $report->hasScreenshot() || $this->screenshotStorage === null) {
            return [];
        }

        $contents = $this->screenshotStorage->getContents($report);
        if ($contents === null) {
            return [];
        }

        return [[
            'name' => $report->getScreenshotOriginalName() ?? 'screenshot',
            'contents' => $contents,
            'contentType' => $report->getScreenshotContentType() ?? BugReportScreenshotStorage::DEFAULT_CONTENT_TYPE,
        ]];
    }

    private function getReplySender(): string
    {
        $recipients = array_values(array_filter($this->recipients));
        if ($recipients === []) {
            throw new RuntimeException('No technical error report sender is configured.');
        }

        return $recipients[0];
    }

    /**
     * @param string[] $recipients
     *
     * @return array<string, null>
     */
    private function recipientsToMap(array $recipients): array
    {
        $mapped = [];
        foreach ($recipients as $recipient) {
            $mapped[$recipient] = null;
        }

        return $mapped;
    }
}
