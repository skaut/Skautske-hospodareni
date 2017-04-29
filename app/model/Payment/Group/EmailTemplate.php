<?php

namespace Model\Payment\Group;

use Model\Payment\Group;
use Model\Payment\InvalidBankAccountException;
use Model\Payment\Mailing\Payment;
use Nette\Utils\Strings;

class EmailTemplate
{

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function evaluate(Group $group, Payment $payment, ?string $bankAccount, string $user): EmailTemplate
    {
        $accountRequired = Strings::contains($this->body, "%qrcode") || Strings::contains($this->body, "%account");
        if ($bankAccount === NULL && $accountRequired) {
            throw new InvalidBankAccountException("Bank account required for email template.");
        }

        $parameters = [
            "%account%" => $bankAccount,
            "%name%" => $payment->getName(),
            "%groupname%" => $group->getName(),
            "%amount%" => $payment->getAmount(),
            "%maturity%" => $payment->getDueDate()->format("j.n.Y"),
            "%vs%" => $payment->getVariableSymbol(),
            "%ks%" => $payment->getConstantSymbol(),
            "%note%" => $payment->getNote(),
            "%user%" => $user,
        ];

        $subject = $this->replace($parameters, $this->subject);

        if (Strings::contains($this->body, "%qrcode")) {
            $parameters["%qrcode%"] = $this->getQrHtml($payment, $bankAccount);
        }

        $body = $this->replace($parameters, $this->body);

        return new EmailTemplate($subject, $body);
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    private function replace(array $parameters, string $template): string
    {
        return str_replace(array_keys($parameters), array_values($parameters), $template);
    }

    private function getQrHtml(Payment $payment, string $bankAccount): string
    {
        $pattern = "#((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})#";

        if (preg_match($pattern, $bankAccount, $account) !== 1) {
            throw new InvalidBankAccountException();
        }

        $params = [
            "accountNumber" => $account["number"],
            "bankCode" => $account["code"],
            "amount" => $payment->getAmount(),
            "currency" => "CZK",
            "size" => "200",
        ];
        if (array_key_exists("prefix", $account) && $account["prefix"] != "") {
            $params["accountPrefix"] = $account["prefix"];
        }
        if ($payment->getVariableSymbol() !== NULL) {
            $params["vs"] = $payment->getVariableSymbol();
        }
        if ($payment->getConstantSymbol() !== NULL) {
            $params["ks"] = $payment->getConstantSymbol();
        }
        if ($payment->getName() !== "") {
            $params["message"] = $payment->getName();
        }

        $file = "http://api.paylibo.com/paylibo/generator/czech/image?" . http_build_query($params);

        return "<img alt=\"QR platbu se nepodařilo zobrazit\" src=\"" . $file . "\">";
    }



}
