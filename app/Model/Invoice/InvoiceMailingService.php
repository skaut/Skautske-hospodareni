<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Mail\IMailerFactory;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\QrPaymentCode;
use App\Model\Services\PdfRenderer;
use App\Model\Services\TemplateFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Nette\Mail\Message;
use Throwable;

use function nl2br;
use function substr;

final class InvoiceMailingService
{
    public function __construct(
        private InvoiceRepository $invoices,
        private EntityManagerInterface $entityManager,
        private IMailerFactory $mailerFactory,
        private IGoogleRepository $googleRepository,
        private TemplateFactory $templateFactory,
        private \App\Model\Export\ExportService $exportService,
        private PdfRenderer $pdfRenderer,
        private IUserRepository $users,
    ) {
    }

    /**
     * @throws EmailTemplateNotSet
     * @throws InvalidOAuth
     * @throws InvoiceAlreadySent
     * @throws InvoiceHasNoEmails
     * @throws InvoiceReminderNotAllowed
     * @throws OAuthNotSet
     */
    public function sendEmail(int $invoiceId, EmailType $emailType, bool $allowResend = false): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (! $invoice instanceof Invoice) {
            throw new InvalidArgumentException('Faktura nebyla nalezena.');
        }

        $user = $this->users->getCurrentUser();
        $userName = $user->getName();
        $attemptTime = new DateTimeImmutable();
        $sequence = $invoice->getSequence();

        try {
            if ($emailType->equalsValue(EmailType::INVOICE_INFO) && $invoice->hasBeenSent() && ! $allowResend) {
                throw InvoiceAlreadySent::withNumber($invoice->getInvoiceNumber());
            }

            if ($emailType->equalsValue(EmailType::INVOICE_REMINDER) && ! $invoice->canSendReminder($attemptTime)) {
                throw InvoiceReminderNotAllowed::withNumber($invoice->getInvoiceNumber());
            }

            $template = $sequence->getEmailTemplate($emailType);

            if ($template === null || ! $sequence->isEmailEnabled($emailType)) {
                throw new EmailTemplateNotSet("E-mail template '".$emailType->getValue()."' not found");
            }

            if ($sequence->getOauthId() === null) {
                throw new OAuthNotSet();
            }

            if ($invoice->getEmailRecipients() === []) {
                throw InvoiceHasNoEmails::withNumber($invoice->getInvoiceNumber());
            }

            $oAuth = $this->googleRepository->find($sequence->getOauthId());

            $mail = (new Message())
                ->setFrom($oAuth->getEmail());

            $qrCodeCid = $this->addQrCodeInlineImage($mail, $template, $invoice);
            $resolvedTemplate = $template->evaluate($invoice, $userName, $qrCodeCid);
            $mailBody = $this->templateFactory->create(
                TemplateFactory::PAYMENT_DETAILS,
                [
                    'body' => nl2br($resolvedTemplate->getBody(), false),
                ],
            );

            $mail->setSubject($resolvedTemplate->getSubject())
                ->setHtmlBody($mailBody, __DIR__);

            foreach ($invoice->getEmailRecipients() as $recipient) {
                $mail->addTo($recipient->getValue());
            }

            $mail->addAttachment(
                $invoice->getInvoiceNumber().'.pdf',
                $this->pdfRenderer->renderToString($this->exportService->getInvoice($invoice)),
                'application/pdf',
            );

            $this->mailerFactory->create($oAuth)->send($mail);
        } catch (Throwable $e) {
            $invoice->recordEmailAttempt($emailType, $attemptTime, $userName, false, $e->getMessage());
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            throw $e;
        }

        $invoice->recordEmailAttempt($emailType, $attemptTime, $userName);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }

    private function addQrCodeInlineImage(Message $mail, EmailTemplate $template, Invoice $invoice): ?string
    {
        if (! $template->containsQrCode()) {
            return null;
        }

        if ($invoice->getPaymentType()->value !== InvoicePaymentType::TRANSFER->value) {
            return null;
        }

        $bankAccount = $invoice->getAccountNumber()?->getNumberWithPrefixAndBankCode();
        if ($bankAccount === null) {
            return null;
        }

        try {
            $part = $mail->addEmbeddedFile(
                'qr-platba.png',
                QrPaymentCode::buildPng(
                    $bankAccount,
                    (string) $invoice->getTotalAmount(),
                    $invoice->getVariableSymbol()->toInt(),
                    8,
                    $invoice->getInvoiceNumber(),
                ),
                'image/png',
            );
        } catch (InvalidBankAccount) {
            return null;
        }

        $contentId = $part->getHeader('Content-ID');

        return substr(is_string($contentId) ? $contentId : '', 1, -1);
    }
}
