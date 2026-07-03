<?php

declare(strict_types=1);

namespace App\Model\BugReport;

use App\Model\BugReport\Entity\TechnicalErrorReport;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Utils\Json;
use RuntimeException;

use function array_filter;
use function implode;
use function parse_url;
use function sprintf;

final class BugReportNotificationService
{
    /** @param string[] $recipients */
    public function __construct(
        private Mailer $debugMailer,
        private bool $sendEmail,
        private bool $productionMode,
        private array $recipients,
        private string $appBaseUrl,
    ) {
    }

    public function notify(TechnicalErrorReport $report): void
    {
        $recipients = array_filter($this->recipients);
        if ($recipients === []) {
            throw new RuntimeException('No technical error report recipient is configured.');
        }

        $host = parse_url($this->appBaseUrl, PHP_URL_HOST) ?: 'localhost';
        $message = (new Message())
            ->setFrom('noreply@'.$host, 'Skautské hospodaření')
            ->setSubject(sprintf('[Skautské hospodaření] Hlášení technické chyby #%d', $report->getId()))
            ->setBody($this->createBody($report));

        foreach ($recipients as $recipient) {
            $message->addTo($recipient);
        }

        $mailer = $this->sendEmail && $this->productionMode
            ? new SendmailMailer()
            : $this->debugMailer;

        $mailer->send($message);
    }

    private function createBody(TechnicalErrorReport $report): string
    {
        $role = array_filter([
            $report->getRoleName(),
            $report->getRoleId() !== null ? 'ID '.$report->getRoleId() : null,
        ]);
        $unit = array_filter([
            $report->getUnitName(),
            $report->getUnitId() !== null ? 'ID '.$report->getUnitId() : null,
        ]);

        return implode("\n", [
            'Byla nahlášena technická chyba ve Skautském hospodaření.',
            '',
            'Hlášení: #'.$report->getId(),
            'Administrace: '.$this->appBaseUrl.'/admin/hlaseni-chyb/'.$report->getId(),
            'Nahlášeno: '.$report->getCreatedAt()->format('Y-m-d H:i:s P'),
            'Uživatel: '.$report->getReporterDisplayName().' (ID '.$report->getReporterUserId().')',
            'Role: '.($role !== [] ? implode(', ', $role) : '-'),
            'Jednotka: '.($unit !== [] ? implode(', ', $unit) : '-'),
            'URL chyby: '.($report->getReportedUrl() ?? '-'),
            'IP: '.($report->getIpAddress() ?? '-'),
            'Release: '.$report->getAppRelease(),
            'User-Agent: '.($report->getUserAgent() ?? '-'),
            '',
            'Popis:',
            $report->getDescription(),
            '',
            'Diagnostika:',
            Json::encode($report->getDiagnostics(), JSON_PRETTY_PRINT),
        ]);
    }
}
