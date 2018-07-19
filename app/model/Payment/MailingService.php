<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use Model\Common\Repositories\IUserRepository;
use Model\Common\UserNotFound;
use Model\Mail\IMailerFactory;
use Model\Payment\Mailing\Payment as MailPayment;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;
use Nette\Utils\Validators;
use function nl2br;
use function rand;

class MailingService
{
    /** @var IGroupRepository */
    private $groups;

    /** @var IMailerFactory */
    private $mailerFactory;

    /** @var IPaymentRepository */
    private $payments;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var IUserRepository */
    private $users;

    /** @var IMailCredentialsRepository */
    private $credentials;

    public function __construct(
        IGroupRepository $groups,
        IMailerFactory $mailerFactory,
        IPaymentRepository $payments,
        IBankAccountRepository $bankAccounts,
        TemplateFactory $templateFactory,
        IUserRepository $users,
        IMailCredentialsRepository $credentials
    ) {
        $this->groups          = $groups;
        $this->mailerFactory   = $mailerFactory;
        $this->payments        = $payments;
        $this->bankAccounts    = $bankAccounts;
        $this->templateFactory = $templateFactory;
        $this->users           = $users;
        $this->credentials     = $credentials;
    }

    /**
     * Sends email to single payment address
     *
     * @throws InvalidEmail
     * @throws PaymentNotFound
     * @throws MailCredentialsNotSet
     * @throws EmailTemplateNotSet
     */
    public function sendEmail(int $paymentId, EmailType $emailType) : void
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
        $this->payments->save($payment);
    }

    /**
     * @return string User's email
     * @throws EmailNotSet
     * @throws GroupNotFound
     * @throws InvalidBankAccount
     * @throws MailCredentialsNotFound
     * @throws MailCredentialsNotSet
     * @throws UserNotFound
     */
    public function sendTestMail(int $groupId) : string
    {
        $group = $this->groups->find($groupId);
        $user  = $this->users->getCurrentUser();

        if ($user->getEmail() === null) {
            throw new EmailNotSet();
        }

        $payment = new MailPayment(
            'Testovací účel',
            $group->getDefaultAmount() ?? rand(50, 1000),
            $user->getEmail(),
            $group->getDueDate() ?? new DateTimeImmutable('+ 2 weeks'),
            rand(1000, 100000),
            $group->getConstantSymbol(),
            'obsah poznámky'
        );

        $this->send($group, $payment, $group->getEmailTemplate(EmailType::get(EmailType::PAYMENT_INFO)));

        return $user->getEmail();
    }


    /**
     * @throws InvalidBankAccount
     * @throws InvalidEmail
     * @throws MailCredentialsNotFound
     * @throws MailCredentialsNotSet
     */
    private function sendForPayment(Payment $paymentRow, Group $group, EmailTemplate $template) : void
    {
        $email = $paymentRow->getEmail();
        if ($email === null || ! Validators::isEmail($email)) {
            throw new InvalidEmail();
        }

        $this->send($group, $this->createPayment($paymentRow), $template);
    }

    /**
     * @throws InvalidBankAccount
     * @throws MailCredentialsNotFound
     * @throws MailCredentialsNotSet
     */
    private function send(Group $group, MailPayment $payment, EmailTemplate $emailTemplate) : void
    {
        if ($group->getSmtpId() === null) {
            throw new MailCredentialsNotSet();
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

        $credentials = $this->credentials->find($group->getSmtpId());

        $mail = (new Message())
            ->addTo($payment->getEmail())
            ->setFrom($credentials->getSender())
            ->setSubject($emailTemplate->getSubject())
            ->setHtmlBody($template, __DIR__);

        $this->mailerFactory->create($credentials)->send($mail);
    }

    private function createPayment(Payment $payment) : MailPayment
    {
        return new MailPayment(
            $payment->getName(),
            $payment->getAmount(),
            $payment->getEmail(),
            $payment->getDueDate(),
            $payment->getVariableSymbol() !== null ? $payment->getVariableSymbol()->toInt() : null,
            $payment->getConstantSymbol(),
            $payment->getNote()
        );
    }
}
