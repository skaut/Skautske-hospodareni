<?php

namespace Model\Payment;

use DateTimeImmutable;
use Dibi\Row;
use Model\DTO\Payment\Payment;
use Model\Mail\IMailerFactory;
use Model\MailTable;
use Model\Payment\QR\IQRGenerator;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IUserRepository;
use Model\PaymentTable;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

class MailingService
{

    /** @var IGroupRepository */
    private $groups;

    /** @var IMailerFactory */
    private $mailerFactory;

    /** @var PaymentTable */
    private $payments;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var IUserRepository */
    private $users;

    /** @var MailTable */
    private $smtps;

    /** @var IQRGenerator */
    private $qr;

    public function __construct(
        IGroupRepository $groups,
        IMailerFactory $mailerFactory,
        PaymentTable $payments,
        IBankAccountRepository $bankAccounts,
        TemplateFactory $templateFactory,
        IUserRepository $users,
        MailTable $smtps,
        IQRGenerator $qr
    )
    {
        $this->groups = $groups;
        $this->mailerFactory = $mailerFactory;
        $this->payments = $payments;
        $this->bankAccounts = $bankAccounts;
        $this->templateFactory = $templateFactory;
        $this->users = $users;
        $this->smtps = $smtps;
        $this->qr = $qr;
    }

    public function sendEmail(int $paymentId, int $userId): void
    {
        $payment = $this->payments->getSimple($paymentId);
        $group = $this->groups->find($payment->groupId);
        $bankAccount = $this->getBankAccount($group->getUnitId());
        $user = $this->users->find($userId);

        $this->sendForPayment($payment, $group, $bankAccount, $user);
    }

    public function sendEmailForGroup(int $groupId, int $userId) : int
    {
        $group = $this->groups->find($groupId);
        $bankAccount = $this->getBankAccount($group->getUnitId());

        $payments = $this->payments->getAllPayments($groupId);
        $user = $this->users->find($userId);

        $sent = 0;
        foreach($payments as $payment) {
            try {
                $this->sendForPayment($payment, $group, $bankAccount, $user);
                $sent++;
            } catch(InvalidEmailException | PaymentFinishedException $e) {}
        }

        return $sent;
    }

    /**
     * @param int $groupId
     * @param int $userId
     * @return string User's email
     * @throws EmailNotSetException
     */
    public function sendTestMail(int $groupId, int $userId): string
    {
        $group = $this->groups->find($groupId);
        $bankAccount = $this->getBankAccount($group->getUnitId());

        $user = $this->users->find($userId);

        if($user->getEmail() === NULL) {
            throw new EmailNotSetException();
        }

        $payment = new Payment(
            'Testovací účel',
            $group->getDefaultAmount() ?? rand(50, 1000),
            $user->getEmail(),
            $group->getDueDate() ?? new DateTimeImmutable('+ 2 weeks'),
            rand(1000, 100000),
            $group->getConstantSymbol(),
            'obsah poznámky'
        );

        $this->send($group, $payment, $bankAccount, $user);

        return $user->getEmail();
    }

    private function getBankAccount(int $unitId) : ?string
    {
        $accounts = $this->bankAccounts->findByUnit($unitId);
        foreach($accounts as $account) {
            if($account->isMain()) {
                return $account->getNumber();
            }
        }
        return NULL;
    }

    private function sendForPayment(Row $paymentRow, Group $group, ?string $bankAccount, User $user) : void
    {
        $state = $paymentRow->state;

        if(!in_array($state, $this->payments->getNonFinalStates())) {
            throw new PaymentFinishedException();
        }

        $payment = new Payment(
            $paymentRow->name,
            $paymentRow->amount,
            $paymentRow->email,
            DateTimeImmutable::createFromMutable($paymentRow->maturity),
            $paymentRow->vs ? (int) $paymentRow->vs : NULL,
            $paymentRow->ks ? (int)$paymentRow->ks : NULL,
            (string)$paymentRow->note
        );
        $this->send($group, $payment, $bankAccount, $user);

        if ($state != PaymentTable::PAYMENT_STATE_SEND) {
            $this->payments->update($paymentRow->id, ['state' => PaymentTable::PAYMENT_STATE_SEND]);
        }

    }

    private function send(Group $group, Payment $payment, ?string $bankAccount, User $user) : void
    {
        $email = $payment->getEmail();
        if ($email === NULL || !Validators::isEmail($email)) {
            throw new InvalidEmailException();
        }

        $body = $group->getEmailTemplate();

        $accountRequired = Strings::contains($body, '%qrcode') || Strings::contains($body, '%account');
        if($bankAccount === NULL && $accountRequired) {
            throw new InvalidBankAccountException('Bank account required for email.');
        }

        $parameters = [
            '%account%' => $bankAccount,
            '%name%' => $payment->getName(),
            '%groupname%' => $group->getName(),
            '%amount%' => $payment->getAmount(),
            '%maturity%' => $payment->getDueDate()->format('j.n.Y'),
            '%vs%' => $payment->getVariableSymbol(),
            '%ks%' => $payment->getConstantSymbol(),
            '%note%' => $payment->getNote(),
            '%user%' => $user->getName(),
        ];

        if (Strings::contains($body, '%qrcode')) {
            $file = $this->qr->generate($bankAccount, $payment);
            $parameters['%qrcode%'] = '<img alt="QR platbu se nepodařilo zobrazit" src="' . $file . '"/>';
        }

        $template = $this->templateFactory->create('payment.base', [
            'body' => str_replace(array_keys($parameters), array_values($parameters), $body),
        ]);

        $mail = (new Message())
            ->addTo($payment->getEmail())
            ->setFrom('platby@skauting.cz') // There must be something, but gmail overwrites it :(
            ->setSubject('Informace o platbě')
            ->setHtmlBody($template, __DIR__);

        $smtp = $this->smtps->get($group->getSmtpId());
        $this->mailerFactory->create($smtp->toArray())->send($mail);
    }

}
