<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Payment\Mailing\Payment;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

use function array_keys;
use function array_values;
use function str_replace;

#[ORM\Embeddable]
class EmailTemplate
{
    #[ORM\Column(type: 'string')]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $body;

    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function evaluate(Group $group, Payment $payment, ?string $bankAccount, string $user, ?string $qrCodeCid = null): EmailTemplate
    {
        $accountRequired = Strings::contains($this->body, '%qrcode') || Strings::contains($this->body, '%account');
        if ($bankAccount === null && $accountRequired) {
            throw new InvalidBankAccount('Bank account required for email template.');
        }

        $parameters = [
            '%account%' => $bankAccount,
            '%name%' => $payment->getName(),
            '%groupname%' => $group->getName(),
            '%amount%' => $payment->getAmount(),
            '%maturity%' => $payment->getDueDate()->format('j.n.Y'),
            '%maturityus%' => $payment->getDueDate()->format('Y-m-d'),
            '%vs%' => $payment->getVariableSymbol(),
            '%ks%' => $payment->getConstantSymbol(),
            '%note%' => $payment->getNote(),
            '%user%' => $user,
        ];

        $subject = $this->replace($parameters, $this->subject);

        if ($bankAccount !== null && Strings::contains($this->body, '%qrcode')) {
            $parameters['%qrcode%'] = $this->getQrHtml($payment, $bankAccount, $qrCodeCid);
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

    public function containsQrCode(): bool
    {
        return Strings::contains($this->body, '%qrcode');
    }

    /** @param mixed[] $parameters */
    private function replace(array $parameters, string $template): string
    {
        return str_replace(array_keys($parameters), array_values($parameters), $template);
    }

    private function getQrHtml(Payment $payment, string $bankAccount, ?string $qrCodeCid): string
    {
        if ($qrCodeCid !== null) {
            return '<img alt="QR platbu se nepodařilo zobrazit" src="cid:'.$qrCodeCid.'">';
        }

        $file = QrPaymentCode::buildImageUrl(
            $bankAccount,
            $payment->getAmount(),
            $payment->getVariableSymbol(),
            $payment->getConstantSymbol(),
            $payment->getName(),
        );

        return '<img alt="QR platbu se nepodařilo zobrazit" src="'.$file.'">';
    }

    public function equals(EmailTemplate $other): bool
    {
        return $other->subject === $this->subject && $other->body === $this->body;
    }
}
