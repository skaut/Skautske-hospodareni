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
use function array_values;
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
        private ?BugReportScreenshotStorage $screenshotStorage = null,
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

        if ($report->getReporterEmail() !== null) {
            $message->addReplyTo($report->getReporterEmail(), $report->getReporterDisplayName());
        }

        $this->attachScreenshot($message, $report);

        foreach ($recipients as $recipient) {
            $message->addTo($recipient);
        }

        $mailer = $this->sendEmail && $this->productionMode
            ? new SendmailMailer()
            : $this->debugMailer;

        $mailer->send($message);
    }

    public function notifyResolution(TechnicalErrorReport $report, string $messageText): void
    {
        $this->notifyClosure(
            $report,
            sprintf('[Skautské hospodaření] Hlášení #%d bylo zpracováno', $report->getId()),
            $this->createResolutionBody($report, $messageText),
        );
    }

    public function notifyRejection(TechnicalErrorReport $report, string $messageText): void
    {
        $this->notifyClosure(
            $report,
            sprintf('[Skautské hospodaření] Hlášení #%d bylo zamítnuto', $report->getId()),
            $this->createRejectionBody($report, $messageText),
        );
    }

    public function notifyReply(TechnicalErrorReport $report, string $messageText): void
    {
        $recipient = $report->getReporterEmail();
        if ($recipient === null) {
            throw new RuntimeException('Technical error report has no reporter email.');
        }

        $sender = $this->getReplySender();
        $message = (new Message())
            ->setFrom($sender, 'Skautské hospodaření')
            ->addTo($recipient, $report->getReporterDisplayName())
            ->setSubject(sprintf('[Skautské hospodaření] Odpověď k hlášení #%d', $report->getId()))
            ->setBody($this->createReplyBody($report, $messageText));

        $this->send($message);
    }

    private function notifyClosure(TechnicalErrorReport $report, string $subject, string $body): void
    {
        $recipient = $report->getReporterEmail();
        if ($recipient === null) {
            throw new RuntimeException('Technical error report has no reporter email.');
        }

        $host = parse_url($this->appBaseUrl, PHP_URL_HOST) ?: 'localhost';
        $message = (new Message())
            ->setFrom('noreply@'.$host, 'Skautské hospodaření')
            ->addTo($recipient, $report->getReporterDisplayName())
            ->setSubject($subject)
            ->setBody($body);

        $this->send($message);
    }

    private function send(Message $message): void
    {
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
            'E-mail uživatele: '.($report->getReporterEmail() ?? '-'),
            'Role: '.($role !== [] ? implode(', ', $role) : '-'),
            'Jednotka: '.($unit !== [] ? implode(', ', $unit) : '-'),
            'URL chyby: '.($report->getReportedUrl() ?? '-'),
            'IP: '.($report->getIpAddress() ?? '-'),
            'Release: '.$report->getAppRelease(),
            'User-Agent: '.($report->getUserAgent() ?? '-'),
            'Screenshot: '.($report->hasScreenshot() ? ($report->getScreenshotOriginalName() ?? 'přiložen') : '-'),
            '',
            'Popis:',
            $report->getDescription(),
            '',
            'Diagnostika:',
            Json::encode($report->getDiagnostics(), JSON_PRETTY_PRINT),
        ]);
    }

    private function createResolutionBody(TechnicalErrorReport $report, string $messageText): string
    {
        return implode("\n", [
            'Dobrý den,',
            '',
            sprintf('vámi nahlášený problém #%d ve Skautském hospodaření byl zpracován.', $report->getId()),
            'Požadavek byl zpracován a oprava je upravena v nové verzi aplikace.',
            '',
            'Zpráva administrátora:',
            $messageText,
            '',
            'Původní hlášení:',
            $report->getDescription(),
            '',
            'URL chyby: '.($report->getReportedUrl() ?? '-'),
            '',
            'Děkujeme za nahlášení.',
            'Skautské hospodaření',
        ]);
    }

    private function createRejectionBody(TechnicalErrorReport $report, string $messageText): string
    {
        return implode("\n", [
            'Dobrý den,',
            '',
            sprintf('vámi nahlášený problém #%d ve Skautském hospodaření byl uzavřen bez opravy.', $report->getId()),
            '',
            'Důvod uzavření:',
            $messageText,
            '',
            'Původní hlášení:',
            $report->getDescription(),
            '',
            'URL chyby: '.($report->getReportedUrl() ?? '-'),
            '',
            'Děkujeme za nahlášení.',
            'Skautské hospodaření',
        ]);
    }

    private function createReplyBody(TechnicalErrorReport $report, string $messageText): string
    {
        return implode("\n", [
            'Dobrý den,',
            '',
            sprintf('posíláme odpověď k vámi nahlášenému problému #%d ve Skautském hospodaření.', $report->getId()),
            '',
            'Zpráva administrátora:',
            $messageText,
            '',
            'Původní hlášení:',
            $report->getDescription(),
            '',
            'URL chyby: '.($report->getReportedUrl() ?? '-'),
            '',
            'Skautské hospodaření',
        ]);
    }

    private function attachScreenshot(Message $message, TechnicalErrorReport $report): void
    {
        if (! $report->hasScreenshot() || $this->screenshotStorage === null) {
            return;
        }

        $contents = $this->screenshotStorage->getContents($report);
        if ($contents === null) {
            return;
        }

        $message->addAttachment(
            $report->getScreenshotOriginalName() ?? 'screenshot',
            $contents,
            $report->getScreenshotContentType() ?? BugReportScreenshotStorage::DEFAULT_CONTENT_TYPE,
        );
    }

    private function getReplySender(): string
    {
        $recipients = array_values(array_filter($this->recipients));
        if ($recipients === []) {
            throw new RuntimeException('No technical error report sender is configured.');
        }

        return $recipients[0];
    }
}
