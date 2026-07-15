<?php

declare(strict_types=1);

namespace App\Model\Mail;

use InvalidArgumentException;

use function sprintf;

enum SystemEmailTemplate: string
{
    case INVOICE_ACCESS_REQUEST_RECEIVED = 'invoice_access_request_received';
    case INVOICE_ACCESS_APPROVED = 'invoice_access_approved';
    case BUG_REPORT_NOTIFICATION = 'bug_report_notification';
    case BUG_REPORT_RESOLUTION = 'bug_report_resolution';
    case BUG_REPORT_REJECTION = 'bug_report_rejection';
    case BUG_REPORT_REPLY = 'bug_report_reply';

    public function templateFile(): string
    {
        return match ($this) {
            self::INVOICE_ACCESS_REQUEST_RECEIVED => __DIR__.'/../emails/userInvoiceAccessRequestReceived.latte',
            self::INVOICE_ACCESS_APPROVED => __DIR__.'/../emails/userInvoiceAccessApproved.latte',
            self::BUG_REPORT_NOTIFICATION => __DIR__.'/../emails/adminBugReportNotification.latte',
            self::BUG_REPORT_RESOLUTION => __DIR__.'/../emails/userBugReportResolution.latte',
            self::BUG_REPORT_REJECTION => __DIR__.'/../emails/userBugReportRejection.latte',
            self::BUG_REPORT_REPLY => __DIR__.'/../emails/userBugReportReply.latte',
        };
    }

    /** @param array<string, mixed> $parameters */
    public function subject(array $parameters): string
    {
        return match ($this) {
            self::INVOICE_ACCESS_REQUEST_RECEIVED => 'Žádost o přístup k fakturaci byla přijata',
            self::INVOICE_ACCESS_APPROVED => 'Fakturace ve Skautském hospodaření je zpřístupněna',
            self::BUG_REPORT_NOTIFICATION => sprintf('[Skautské hospodaření] Hlášení technické chyby #%d', $this->requireInt($parameters, 'reportId')),
            self::BUG_REPORT_RESOLUTION => sprintf('[Skautské hospodaření] Hlášení #%d bylo zpracováno', $this->requireInt($parameters, 'reportId')),
            self::BUG_REPORT_REJECTION => sprintf('[Skautské hospodaření] Hlášení #%d bylo zamítnuto', $this->requireInt($parameters, 'reportId')),
            self::BUG_REPORT_REPLY => sprintf('[Skautské hospodaření] Odpověď k hlášení #%d', $this->requireInt($parameters, 'reportId')),
        };
    }

    /** @param array<string, mixed> $parameters */
    private function requireInt(array $parameters, string $key): int
    {
        $value = $parameters[$key] ?? null;
        if (! is_int($value)) {
            throw new InvalidArgumentException(sprintf('System email template parameter "%s" must be an integer.', $key));
        }

        return $value;
    }
}
