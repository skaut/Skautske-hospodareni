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
 * @ORM\Table(name="ac_camp_participants")
 */
class Payment
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer", name="participantId", options={"unsigned"=true})
     *
     * @var int
     */
    private $participantId;

    /**
     * @ORM\Embedded(class=Event::class)
     *
     * @var Event
     */
    private $event;

    /**
     * @ORM\Column(type="money")
     *
     * @var Money
     */
    private $payment;

    /**
     * @ORM\Column(type="money")
     *
     * @var Money
     */
    private $repayment;

    /**
     * @ORM\Column(type="string", name="isAccount", options={"default"="N", "comment"="placeno na účet?"})
     *
     * @var string
     */
    private $account;

    public function __construct(int $participantId, Event $event, ?Money $payment = null, ?Money $repayment = null, string $account = 'N')
    {
        $this->participantId = $participantId;
        $this->event         = $event;
        $this->payment       = $payment ?? MoneyFactory::zero();
        $this->repayment     = $repayment ?? MoneyFactory::zero();
        $this->account       = $account;
    }

    public function getParticipantId() : int
    {
        return $this->participantId;
    }

    public function getPayment() : Money
    {
        return $this->payment;
    }

    public function getRepayment() : Money
    {
        return $this->repayment;
    }

    public function getAccount() : string
    {
        return $this->account;
    }

    public function setPayment(Money $payment) : void
    {
        $this->payment = $payment;
    }

    public function setRepayment(Money $repayment) : void
    {
        $this->repayment = $repayment;
    }

    public function setAccount(string $account) : void
    {
        if (! in_array($account, ['Y', 'N'])) {
            throw new InvalidArgumentException("Payment attribute account shouldn't be " . $account);
        }
        $this->account = $account;
    }
}
