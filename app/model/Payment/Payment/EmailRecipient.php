<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Doctrine\ORM\Mapping as ORM;
use Model\Common\EmailAddress;
use Model\Payment\Payment;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_payment_email_recipients")
 */
class EmailRecipient
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /** @ORM\ManyToOne(targetEntity=Payment::class, inversedBy="emailRecipients") */
    private Payment $payment;

    /** @ORM\Column(type="email_address") */
    private EmailAddress $emailAddress;

    public function __construct(Payment $payment, EmailAddress $emailAddress)
    {
        $this->payment      = $payment;
        $this->emailAddress = $emailAddress;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
