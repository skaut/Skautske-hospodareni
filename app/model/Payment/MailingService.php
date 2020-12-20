<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use Model\Common\EmailAddress;
use Model\Common\Repositories\IUserRepository;
use Model\Common\UserNotFound;
use Model\Google\Exception\OAuthNotSet;
use Model\Google\InvalidOAuth;
use Model\Mail\IMailerFactory;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\Mailing\Payment as MailPayment;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;

use function nl2br;
use function rand;

class MailingService
{
    private IGroupRepository $groups;

    private IMailerFactory $mailerFactory;

    private IPaymentRepository $payments;

    private IBankAccountRepository $bankAccounts;

    private TemplateFactory $templateFactory;

    private IUserRepository $users;

    private IGoogleRepository $googleRepository;

    public function __construct(
        IGroupRepository $groups,
        IMailerFactory $mailerFactory,
        IPaymentRepository $payments,
        IBankAccountRepository $bankAccounts,
        TemplateFactory $templateFactory,
        IUserRepository $users,
        IGoogleRepository $googleRepository
    ) {
        $this->groups           = $groups;
        $this->mailerFactory    = $mailerFactory;
        $this->payments         = $payments;
        $this->bankAccounts     = $bankAccounts;
        $this->templateFactory  = $templateFactory;
        $this->users            = $users;
        $this->googleRepository = $googleRepository;
    }

    /**
     * Sends email to single payment address
     *
     * @throws PaymentHasNoEmails
     * @throws PaymentNotFound
     * @throws InvalidOAuth
     * @throws EmailTemplateNotSet
     * @throws OAuthNotSet
     */
    public function sendEmail(int $paymentId, EmailType $emailType): void
    {
        $payment = $this->payments->find($paymentId);
        $group   = $this->groups->find($payment->getGroupId());

        $template = $group->getEmailTemplate($emailType);

        if ($template === null || ! $group->isEmailEnabled($emailType)) {
            throw new EmailTemplateNotSet(
                "Email template '" . $emailType->getValue() . "' not found"
            );
        }

        $this->sendForPayment($payment, $group, $template);

        $payment->recordSentEmail($emailType, new DateTimeImmutable(), $this->users->getCurrentUser()->getName());

        $this->payments->save($payment);
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
        $user  = $this->users->getCurrentUser();

        if ($user->getEmail() === null) {
            throw new EmailNotSet();
        }

        $payment = new MailPayment(
            'Testovací účel',
            $group->getDefaultAmount() ?? rand(50, 1000),
            [new EmailAddress($user->getEmail())],
            $group->getDueDate() ?? new DateTimeImmutable('+ 2 weeks'),
            rand(1000, 100000),
            $group->getConstantSymbol(),
            'obsah poznámky'
        );

        $this->send($group, $payment, $group->getEmailTemplate(EmailType::get(EmailType::PAYMENT_INFO)));

        return $user->getEmail();
    }

    /**
     * @throws BankAccountNotFound
     * @throws InvalidBankAccount
     * @throws PaymentHasNoEmails
     * @throws InvalidOAuth
     * @throws UserNotFound
     * @throws OAuthNotSet
     */
    private function sendForPayment(Payment $paymentRow, Group $group, EmailTemplate $template): void
    {
        $this->send($group, $this->createPayment($paymentRow), $template);
    }

    /**
     * @throws InvalidBankAccount
     * @throws BankAccountNotFound
     * @throws UserNotFound
     * @throws OAuthNotSet
     * @throws PaymentHasNoEmails
     */
    private function send(Group $group, MailPayment $payment, EmailTemplate $emailTemplate): void
    {
        if ($group->getOauthId() === null) {
            throw new OAuthNotSet();
        }

        if ($payment->getRecipients() === []) {
            throw PaymentHasNoEmails::withName($payment->getName());
        }

        $user = $this->users->getCurrentUser();

        $bankAccount       = $group->getBankAccountId() !== null
            ? $this->bankAccounts->find($group->getBankAccountId())
            : null;
        $bankAccountNumber = $bankAccount !== null ? (string) $bankAccount->getNumber() : null;

        $emailTemplate = $emailTemplate->evaluate($group, $payment, $bankAccountNumber, $user->getName());

        $template = $this->templateFactory->create(
            TemplateFactory::PAYMENT_DETAILS,
            [
                'body' => nl2br($emailTemplate->getBody(), false),
            ]
        );

        $oAuth = $this->googleRepository->find($group->getOauthId());
        $mail  = (new Message())
            ->setFrom($oAuth->getEmail())
            ->setSubject($emailTemplate->getSubject())
            ->setHtmlBody($template, __DIR__);

        foreach ($payment->getRecipients() as $emailRecipient) {
            $mail->addTo($emailRecipient->getValue());
        }

        $this->mailerFactory->create($oAuth)->send($mail);
    }

    private function createPayment(Payment $payment): MailPayment
    {
        return new MailPayment(
            $payment->getName(),
            $payment->getAmount(),
            $payment->getEmailRecipients(),
            $payment->getDueDate(),
            $payment->getVariableSymbol() !== null ? $payment->getVariableSymbol()->toInt() : null,
            $payment->getConstantSymbol(),
            $payment->getNote()
        );
    }
}
