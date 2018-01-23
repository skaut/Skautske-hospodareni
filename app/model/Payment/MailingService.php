<?php

namespace Model\Payment;

use DateTimeImmutable;
use Model\Payment\Mailing\Payment as MailPayment;
use Model\Mail\IMailerFactory;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Common\Repositories\IUserRepository;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;
use Nette\Utils\Validators;

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
    )
    {
        $this->groups = $groups;
        $this->mailerFactory = $mailerFactory;
        $this->payments = $payments;
        $this->bankAccounts = $bankAccounts;
        $this->templateFactory = $templateFactory;
        $this->users = $users;
        $this->credentials = $credentials;
    }

    /**
     * Sends email to single payment address
     *
     * @param int $paymentId
     * @throws InvalidEmailException
     * @throws PaymentNotFoundException
     * @throws MailCredentialsNotSetException
     * @throws EmailTemplateNotSetException
     */
    public function sendEmail(int $paymentId, EmailType $emailType): void
    {
        $payment = $this->payments->find($paymentId);
        $group = $this->groups->find($payment->getGroupId());

        $template = $group->getEmailTemplate($emailType);

        if ($template === NULL || ! $group->isEmailEnabled($emailType)) {
            throw new EmailTemplateNotSetException(
                "Email template '" . $emailType->getValue() . "' not found"
            );
        }

        $this->sendForPayment($payment, $group, $template);
        $this->payments->save($payment);
    }

    /**
     * @return string User's email
     * @throws EmailNotSetException
     * @throws MailCredentialsNotSetException
     */
    public function sendTestMail(int $groupId): string
    {
        $group = $this->groups->find($groupId);
        $user = $this->users->getCurrentUser();

        if($user->getEmail() === NULL) {
            throw new EmailNotSetException();
        }

        $payment = new MailPayment(
            "Testovací účel",
            $group->getDefaultAmount() ?? rand(50, 1000),
            $user->getEmail(),
            $group->getDueDate() ?? new DateTimeImmutable('+ 2 weeks'),
            rand(1000, 100000),
            $group->getConstantSymbol(),
            "obsah poznámky"
        );

        $this->send($group, $payment, $group->getEmailTemplate(EmailType::get(EmailType::PAYMENT_INFO)));

        return $user->getEmail();
    }


    /**
     * @throws InvalidBankAccountException
     * @throws InvalidEmailException
     * @throws MailCredentialsNotFound
     * @throws MailCredentialsNotSetException
     */
    private function sendForPayment(Payment $paymentRow, Group $group, EmailTemplate $template) : void
    {
        $email = $paymentRow->getEmail();
        if ($email === NULL || !Validators::isEmail($email)) {
            throw new InvalidEmailException();
        }

        $this->send($group, $this->createPayment($paymentRow), $template);
    }

    /**
     * @throws InvalidBankAccountException
     * @throws MailCredentialsNotFound
     * @throws MailCredentialsNotSetException
     */
    private function send(Group $group, MailPayment $payment, EmailTemplate $emailTemplate) : void
    {
        if($group->getSmtpId() === NULL) {
            throw new MailCredentialsNotSetException();
        }

        $user = $this->users->getCurrentUser();

        $bankAccount = $group->getBankAccountId() !== NULL
            ? $this->bankAccounts->find($group->getBankAccountId())
            : NULL;
        $bankAccountNumber = $bankAccount !== NULL ? (string)$bankAccount->getNumber() : NULL;

        $emailTemplate = $emailTemplate->evaluate($group, $payment, $bankAccountNumber, $user->getName());

        $template = $this->templateFactory->create(TemplateFactory::PAYMENT_DETAILS, [
            'body' => nl2br($emailTemplate->getBody(), FALSE),
        ]);

        $credentials = $this->credentials->find($group->getSmtpId());

        $mail = (new Message())
            ->addTo($payment->getEmail())
            ->setFrom($credentials->getSender())
            ->setSubject($emailTemplate->getSubject())
            ->setHtmlBody($template, __DIR__);

        $this->mailerFactory->create($credentials)->send($mail);
    }

    private function createPayment(Payment $payment): MailPayment
    {
        return new MailPayment(
                $payment->getName(),
                $payment->getAmount(),
                $payment->getEmail(),
                $payment->getDueDate(),
                $payment->getVariableSymbol() !== NULL ? $payment->getVariableSymbol()->toInt() : NULL,
                $payment->getConstantSymbol(),
                $payment->getNote()
        );
    }

}
