<?php

declare(strict_types=1);

namespace App\Model\Payment\Payment;

use App\Model\Payment\EmailType;
use App\Model\Payment\Payment;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pa_payment_sent_emails')]
class SentEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Payment::class, inversedBy: 'sentEmails')]
    #[ORM\JoinColumn(nullable: false)]
    private Payment $payment;

    /**
     * @var EmailType
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    #[ORM\Column(type: 'payment_email_type')]
    private $type;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $time;

    #[ORM\Column(type: 'string')]
    private string $senderName;

    public function __construct(Payment $payment, EmailType $type, DateTimeImmutable $time, string $senderName)
    {
        $this->payment = $payment;
        $this->type = $type;
        $this->time = $time;
        $this->senderName = $senderName;
    }

    public function getType(): EmailType
    {
        return $this->type;
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }
}
