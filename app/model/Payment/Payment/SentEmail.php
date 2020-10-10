<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\EmailType;
use Model\Payment\Payment;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_payment_sent_emails")
 */
class SentEmail
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Payment::class, inversedBy="sentEmails")
     */
    private Payment $payment;

    /**
     * @ORM\Column(type="string_enum")
     *
     * @var EmailType
     * @Enum(class=EmailType::class)
     */
    private $type;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $time;

    /**
     * @ORM\Column(type="string")
     */
    private string $senderName;

    public function __construct(Payment $payment, EmailType $type, DateTimeImmutable $time, string $senderName)
    {
        $this->payment    = $payment;
        $this->type       = $type;
        $this->time       = $time;
        $this->senderName = $senderName;
    }

    public function getType() : EmailType
    {
        return $this->type;
    }

    public function getTime() : DateTimeImmutable
    {
        return $this->time;
    }

    public function getSenderName() : string
    {
        return $this->senderName;
    }
}
