<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\Mail\SystemEmailTemplate;
use App\Model\Mail\SystemMailer;
use App\Model\User\Entity\InvoiceAccessRequest;

final class InvoiceAccessNotificationService
{
    public function __construct(private SystemMailer $systemMailer)
    {
    }

    public function notifyRequestReceived(InvoiceAccessRequest $request): bool
    {
        $recipient = $request->getRequesterEmail();
        if ($recipient === null) {
            return false;
        }

        $this->systemMailer->send(
            SystemEmailTemplate::INVOICE_ACCESS_REQUEST_RECEIVED,
            [],
            [$recipient => $request->getDisplayName()],
        );

        return true;
    }

    public function notifyAccessApproved(InvoiceAccessRequest $request): bool
    {
        $recipient = $request->getRequesterEmail();
        if ($recipient === null) {
            return false;
        }

        $this->systemMailer->send(
            SystemEmailTemplate::INVOICE_ACCESS_APPROVED,
            [],
            [$recipient => $request->getDisplayName()],
        );

        return true;
    }
}
