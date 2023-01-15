<?php

declare(strict_types=1);

namespace Model\Participant;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Model\Participant\Payment\Event;
use Model\Utils\MoneyFactory;
use Money\Money;

use function in_array;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ac_participants",
 *     indexes={
 *         @ORM\Index(name="eventId", columns={"event_id"}),
 *     }
 * )
 */
class Payment
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="payment_id")
     */
    private PaymentId $id;

    /** @ORM\Column(type="integer") */
    private int $participantId;

    /** @ORM\Embedded(class=Event::class) */
    private Event $event;

    /** @ORM\Column(type="money") */
    private Money $payment;

    /** @ORM\Column(type="money") */
    private Money $repayment;

    /** @ORM\Column(type="string") */
    private string $account;

    public function __construct(PaymentId $id, int $participantId, Event $event, Money|null $payment = null, Money|null $repayment = null, string $account = 'N')
    {
        $this->id            = $id;
        $this->participantId = $participantId;
        $this->event         = $event;
        $this->payment       = $payment ?? MoneyFactory::zero();
        $this->repayment     = $repayment ?? MoneyFactory::zero();
        $this->account       = $account;
    }

    public function getId(): PaymentId
    {
        return $this->id;
    }

    public function getParticipantId(): int
    {
        return $this->participantId;
    }

    public function getPayment(): Money
    {
        return $this->payment;
    }

    public function getRepayment(): Money
    {
        return $this->repayment;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function setPayment(Money $payment): void
    {
        $this->payment = $payment;
    }

    public function setRepayment(Money $repayment): void
    {
        $this->repayment = $repayment;
    }

    public function setAccount(string $account): void
    {
        if (! in_array($account, ['Y', 'N'])) {
            throw new InvalidArgumentException("Payment attribute account shouldn't be " . $account);
        }

        $this->account = $account;
    }
}
