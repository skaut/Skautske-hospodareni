<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Common\Repositories\IUserRepository;
use App\Model\Common\UserNotFound;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Mail\IMailerFactory;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\Mailing\ComposedEmail;
use App\Model\Payment\Mailing\Payment as MailPayment;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Services\TemplateFactory;
use App\Model\Utils\MoneyFactory;
use DateTimeImmutable;
use Nette\Mail\Message;
use RuntimeException;

use function nl2br;
use function rand;
use function substr;

class MailingService
{
    public function __construct(
        private IGroupRepository $groups,
        private IMailerFactory $mailerFactory,
        private IBankAccountRepository $bankAccounts,
        private TemplateFactory $templateFactory,
        private IUserRepository $users,
        private IGoogleRepository $googleRepository,
    ) {
    }

    /**
     * @return string User's email
     *
     * @throws BankAccountNotFound
     * @throws EmailNotSet
     * @throws GroupNotFound
     * @throws InvalidBankAccount
     * @throws InvalidOAuth
     * @throws UserNotFound
     */
    public function sendTestMail(int $groupId): string
    {
        $group = $this->groups->find($groupId);
        $user = $this->users->getCurrentUser();

        if ($user->getEmail() === null) {
            throw new EmailNotSet();
        }

        $payment = new MailPayment(
            'Testovací účel',
            $group->getDefaultAmount() ?? MoneyFactory::fromDecimal((string) rand(50, 1000)),
            [new EmailAddress($user->getEmail())],
            $group->getDueDate()?->toNative() ?? new DateTimeImmutable('+ 2 weeks'),
            rand(1000, 100000),
            $group->getConstantSymbol(),
            'obsah poznámky',
        );

        $this->send($group, $payment, $group->getEmailTemplate(EmailType::get(EmailType::PAYMENT_INFO)) ?? throw new RuntimeException('Skupina nemá e-mailovou šablonu.'), $user->getName());

        return $user->getEmail();
    }

    /**
     * Builds the e-mail of a payment without sending it, so the task queue can send it as a separate step.
     *
     * @param string $senderName who sends the e-mail, it fills the user variable of the template
     *
     * @throws EmailTemplateNotSet
     * @throws OAuthNotSet
     * @throws OAuthNotFound
     * @throws PaymentHasNoEmails
     * @throws InvalidBankAccount
     * @throws BankAccountNotFound
     */
    public function composeForPayment(Payment $payment, Group $group, EmailType $emailType, string $senderName): ComposedEmail
    {
        $template = $group->getEmailTemplate($emailType);

        if ($template === null || ! $group->isEmailEnabled($emailType)) {
            throw new EmailTemplateNotSet("E-mail template '".$emailType->getValue()."' not found");
        }

        return $this->compose($group, $this->createPayment($payment), $template, $senderName);
    }

    /**
     * @throws InvalidBankAccount
     * @throws BankAccountNotFound
     * @throws UserNotFound
     * @throws OAuthNotSet
     * @throws PaymentHasNoEmails
     */
    private function send(Group $group, MailPayment $payment, EmailTemplate $emailTemplate, string $userName): void
    {
        $email = $this->compose($group, $payment, $emailTemplate, $userName);

        $this->mailerFactory->create($email->sender)->send($email->message);
    }

    /**
     * @throws InvalidBankAccount
     * @throws BankAccountNotFound
     * @throws OAuthNotSet
     * @throws OAuthNotFound
     * @throws PaymentHasNoEmails
     */
    private function compose(Group $group, MailPayment $payment, EmailTemplate $emailTemplate, string $userName): ComposedEmail
    {
        if ($group->getOauthId() === null) {
            throw new OAuthNotSet();
        }

        if ($payment->getRecipients() === []) {
            throw PaymentHasNoEmails::withName($payment->getName());
        }

        $bankAccount = $group->getBankAccountId() !== null
            ? $this->bankAccounts->find($group->getBankAccountId())
            : null;
        $bankAccountNumber = $bankAccount !== null ? (string) $bankAccount->getNumber() : null;

        $oAuth = $this->googleRepository->find($group->getOauthId());
        $mail = (new Message())
            ->setFrom($oAuth->getEmail());

        $qrCodeCid = $this->addQrCodeInlineImage($mail, $emailTemplate, $payment, $bankAccountNumber);
        $emailTemplate = $emailTemplate->evaluate($group, $payment, $bankAccountNumber, $userName, $qrCodeCid);

        $template = $this->templateFactory->create(
            TemplateFactory::PAYMENT_DETAILS,
            [
                'body' => nl2br($emailTemplate->getBody(), false),
            ],
        );

        $mail->setSubject($emailTemplate->getSubject())
            ->setHtmlBody($template, __DIR__);

        foreach ($payment->getRecipients() as $emailRecipient) {
            $mail->addTo($emailRecipient->getValue());
        }

        return new ComposedEmail($mail, $oAuth);
    }

    private function addQrCodeInlineImage(Message $mail, EmailTemplate $emailTemplate, MailPayment $payment, ?string $bankAccountNumber): ?string
    {
        if (! $emailTemplate->containsQrCode()) {
            return null;
        }

        if ($bankAccountNumber === null) {
            throw new InvalidBankAccount('Bank account required for QR code.');
        }

        $part = $mail->addEmbeddedFile(
            'qr-platba.png',
            QrPaymentCode::buildPng(
                $bankAccountNumber,
                MoneyFactory::toDecimal($payment->getAmount()),
                $payment->getVariableSymbol(),
                $payment->getConstantSymbol(),
                $payment->getName(),
            ),
            'image/png',
        );

        $contentId = $part->getHeader('Content-ID');

        return substr(is_string($contentId) ? $contentId : '', 1, -1);
    }

    private function createPayment(Payment $payment): MailPayment
    {
        return new MailPayment(
            $payment->getName(),
            $payment->getAmount(),
            $payment->getEmailRecipients(),
            $payment->getDueDate()->toNative(),
            $payment->getVariableSymbol()?->toInt(),
            $payment->getConstantSymbol(),
            $payment->getNote(),
        );
    }
}
