<?php

namespace Model\Payment;

use DateTimeImmutable;
use Dibi\Row;
use Model\DTO\Payment\Payment;
use Model\Mail\IMailerFactory;
use Model\Payment\QR\IQRGenerator;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\PaymentTable;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;
use Nette\Utils\Image;
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

    /** @var IQRGenerator */
    private $qr;

    public function __construct(
        IGroupRepository $groups,
        IMailerFactory $mailerFactory,
        PaymentTable $payments,
        IBankAccountRepository $bankAccounts,
        TemplateFactory $templateFactory,
        IQRGenerator $qr
    )
    {
        $this->groups = $groups;
        $this->mailerFactory = $mailerFactory;
        $this->payments = $payments;
        $this->bankAccounts = $bankAccounts;
        $this->templateFactory = $templateFactory;
        $this->qr = $qr;
    }

    public function sendEmail(int $paymentId)
    {
        $payment = $this->payments->getSimple($paymentId);
        $group = $this->groups->find($payment->groupId);
        $bankAccount = $this->getBankAccount($group->getUnitId());

        $this->sendForPayment($payment, $group, $bankAccount);
    }

    public function sendEmailForGroup(int $groupId) : int
    {
        $group = $this->groups->find($groupId);
        $bankAccount = $this->getBankAccount($group->getUnitId());

        $payments = $this->payments->getAllPayments($groupId);

        $sent = 0;
        foreach($payments as $payment) {
            try {
                $this->sendForPayment($payment, $group, $bankAccount);
                $sent++;
            } catch(InvalidEmailException | PaymentFinishedException $e) {}
        }

        return $sent;
    }

    public function sendTestMail(int $groupId, string $email) : void
    {
        $group = $this->groups->find($groupId);
        $bankAccount = $this->getBankAccount($group->getUnitId());

        $payment = new Payment(
            'Testovací účel',
            $group->getDefaultAmount() ?? rand(50, 1000),
            $email,
            $group->getDueDate() ?? new DateTimeImmutable('+ 2 weeks'),
            rand(1000, 100000),
            $group->getConstantSymbol(),
            'obsah poznámky'
        );

        $this->send($group, $payment, $bankAccount);
    }

    private function getBankAccount(int $unitId) : ?string
    {
        $accounts = $this->bankAccounts->findByUnit($unitId);

        if($accounts) {
            return $accounts[0]->getNumber();
        }

        return NULL;
    }

    private function sendForPayment(Row $paymentRow, Group $group, ?string $bankAccount) : void
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
            $paymentRow->vs,
            $paymentRow->ks,
            $paymentRow->note
        );
        $this->send($group, $payment, $bankAccount);

        if ($state != PaymentTable::PAYMENT_STATE_SEND) {
            $this->payments->update($paymentRow->id, ['state' => PaymentTable::PAYMENT_STATE_SEND]);
        }

    }

    /**
     * @TODO replace with class (QRGenerator etc.)
     * @param string|NULL $bankAccount
     * @param Row $payment
     * @return string
     */
    private function generateQRFile(?string $bankAccount, Payment $payment) : string
    {
        preg_match('#((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})#', $bankAccount, $account);

        $params = [
            "accountNumber" => $account['number'],
            "bankCode" => $account['code'],
            "amount" => $payment->getAmount(),
            "currency" => "CZK",
            "date" => $payment->getDueDate()->format("Y-m-d"),
            "size" => "200",
        ];
        if (array_key_exists('prefix', $account) && $account['prefix'] != '') {
            $params['accountPrefix'] = $account['prefix'];
        }
        if ($payment->getVariableSymbol() != '') {
            $params['vs'] = $payment->getVariableSymbol();
        }
        if ($payment->getConstantSymbol() != '') {
            $params['ks'] = $payment->getConstantSymbol();
        }
        if ($payment->getName() != '') {
            $params['message'] = $payment->getName();
        }

        $url = 'http://api.paylibo.com/paylibo/generator/czech/image?' . http_build_query($params);
        $filename = "qr_" . date("y_m_d_H_i_s_") . (rand(10, 20) * microtime(TRUE)) . ".png";
        Image::fromFile($url)->save(self::QR_LOCATION . $filename);

        return  $filename;
    }

    private function send(Group $group, Payment $payment, ?string $bankAccount) : void
    {
        $email = $payment->getEmail();
        if ($email === NULL || !Validators::isEmail($email)) {
            throw new InvalidEmailException();
        }

        $template = $this->templateFactory->create();
        $template->setFile(__DIR__ . '/mail.base.latte');

        $parameters = [
            '%account%' => $bankAccount,
            '%name%' => $payment->getName(),
            '%groupname%' => $group->getName(),
            '%amount%' => $payment->getAmount(),
            '%maturity%' => $payment->getDueDate()->format('j.n.Y'),
            '%vs%' => $payment->getVariableSymbol(),
            '%ks%' => $payment->getConstantSymbol(),
            '%note%' => $payment->getNote(),
        ];


        if (strpos($group->getEmailTemplate(), '%qrcode') !== FALSE) {
            $file = $this->qr->generate($bankAccount, $payment);
            $parameters['%qrcode%'] = '<img alt="QR platbu se nepodařilo zobrazit" src="' . $file . '"/>';
        }

        $template->body = str_replace(
            array_keys($parameters),
            array_values($parameters),
            $group->getEmailTemplate()
        );

        $mail = (new Message())
            ->addTo($payment->getEmail())
            ->setFrom('platby@skauting.cz') // There must be something, but gmail overwrites it :(
            ->setSubject('Informace o platbě')
            ->setHtmlBody($template, __DIR__);

        $this->mailerFactory->create($group->getSmtpId())->send($mail);
    }

}
