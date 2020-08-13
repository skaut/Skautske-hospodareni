<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use Model\Common\Repositories\IUserRepository;
use Model\Common\UserNotFound;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\InvalidOAuth;
use Model\Mail\IMailerFactory;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\Mailing\Payment as MailPayment;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
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

    /** @var IGoogleRepository */
    private $googleRepository;

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
     * @throws InvalidEmail
     * @throws PaymentNotFound
     * @throws InvalidOAuth
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
     * @throws BankAccountNotFound
     * @throws InvalidBankAccount
     * @throws InvalidEmail
     * @throws InvalidOAuth
     * @throws UserNotFound
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
     * @throws BankAccountNotFound
     * @throws UserNotFound
     */
    private function send(Group $group, MailPayment $payment, EmailTemplate $emailTemplate) : void
    {
        if ($group->getOauthId() === null) {
            throw new OAuthNotFound();
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
            ->addTo($payment->getEmail())
            ->setFrom($oAuth->getEmail())
            ->setSubject($emailTemplate->getSubject())
            ->setHtmlBody($template, __DIR__);

        $this->mailerFactory->create($oAuth)->send($mail);
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
