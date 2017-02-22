<?php

namespace Model\Payment;

use Dibi\Row;
use Model\Mail\IMailerFactory;
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

    private const QR_LOCATION = WWW_DIR . '/webtemp/';

    /**
     * MailingService constructor.
     * @param IGroupRepository $groups
     * @param IMailerFactory $mailerFactory
     * @param PaymentTable $payments
     * @param IBankAccountRepository $bankAccounts
     * @param TemplateFactory $templateFactory
     */
    public function __construct(IGroupRepository $groups, IMailerFactory $mailerFactory, PaymentTable $payments, IBankAccountRepository $bankAccounts, TemplateFactory $templateFactory)
    {
        $this->groups = $groups;
        $this->mailerFactory = $mailerFactory;
        $this->payments = $payments;
        $this->bankAccounts = $bankAccounts;
        $this->templateFactory = $templateFactory;
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

    private function getBankAccount(int $unitId) : ?string
    {
        $accounts = $this->bankAccounts->findByUnit($unitId);
        if($accounts) {
            return $accounts[0]->getNumber();
        }
    }

    private function sendForPayment(Row $payment, Group $group, ?string $bankAccount)
    {

        if(!in_array($payment->state, $this->payments->getNonFinalStates())) {
            throw new PaymentFinishedException();
        }

        if(!$payment->email || !Validators::isEmail($payment->email)) {
            throw new InvalidEmailException();
        }

        $template = $this->templateFactory->create();
        $template->setFile(__DIR__ . '/mail.base.latte');

        $parameters = [
            '%account%' => $bankAccount,
            '%name%' => $payment->name,
            '%groupname%' => $group->getName(),
            '%amount%' => $payment->amount,
            '%maturity%' => $payment->maturity->format('j.n.Y'),
            '%vs%' => $payment->vs,
            '%ks%' => $payment->ks,
            '%note%' => $payment->note,
        ];

        $qr = strpos($group->getEmailTemplate(), '%qrcode') !== FALSE;
        $qrFile = NULL;

        if($qr) {
            $qrFile = $this->generateQRFile($bankAccount, $payment);
            $parameters['%qrcode%'] = '<img alt="QR platbu se nepodařilo zobrazit" src="' . $qrFile . '"/>';
        }


        $template->body = str_replace(
            array_keys($parameters),
            array_values($parameters),
            $group->getEmailTemplate()
        );

        $mail = (new Message())
            ->addTo($payment->email)
            ->setSubject('Informace o platbě')
            ->setHtmlBody($template, self::QR_LOCATION);

        $this->mailerFactory->create($group->getSmtpId())->send($mail);

        if ($payment->state != PaymentTable::PAYMENT_STATE_SEND) {
            $this->payments->update($payment->id, ["state" => PaymentTable::PAYMENT_STATE_SEND]);
        }

        if (is_file($qr)) {
            unlink(self::QR_LOCATION . $qrFile);
        }
        return TRUE;

    }

    /**
     * @TODO replace with class (QRGenerator etc.)
     * @param string|NULL $bankAccount
     * @param Row $payment
     * @return string
     */
    private function generateQRFile(?string $bankAccount, Row $payment) : string
    {
        preg_match('#((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})#', $bankAccount, $account);

        $params = [
            "accountNumber" => $account['number'],
            "bankCode" => $account['code'],
            "amount" => $payment->amount,
            "currency" => "CZK",
            "date" => $payment->maturity->format("Y-m-d"),
            "size" => "200",
        ];
        if (array_key_exists('prefix', $account) && $account['prefix'] != '') {
            $params['accountPrefix'] = $account['prefix'];
        }
        if ($payment->vs != '') {
            $params['vs'] = $payment->vs;
        }
        if ($payment->ks != '') {
            $params['ks'] = $payment->ks;
        }
        if ($payment->name != '') {
            $params['message'] = $payment->name;
        }

        $url = 'http://api.paylibo.com/paylibo/generator/czech/image?' . http_build_query($params);
        $filename = "qr_" . date("y_m_d_H_i_s_") . (rand(10, 20) * microtime(TRUE)) . ".png";
        Image::fromFile($url)->save(self::QR_LOCATION . $filename);

        return  $filename;
    }

}