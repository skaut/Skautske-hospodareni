<?php

declare(strict_types=1);

namespace Model\Participant;

use Doctrine\ORM\Mapping as ORM;
use Model\Event\SkautisEventId;
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
     * @var int
     * @ORM\Id()
     * @ORM\Column(type="integer", name="participantId", options={"unsigned"=true})
     */
    private $participantId;

    /**
     * @var SkautisEventId
     * @ORM\Column(type="integer", name="actionId", options={"unsigned"=true})
     */
    private $actionId;

    /**
     * @var Money
     * @ORM\Column(type="money")
     */
    private $payment;

    /**
     * @var Money
     * @ORM\Column(type="money")
     */
    private $repayment;

    /**
     * @var string
     * @ORM\Column(type="string", name="isAccount", options={"default"="N", "comment"="placeno na účet?"})
     */
    private $account;

    public function __construct(int $participantId, SkautisEventId $eventId, ?Money $payment = null, ?Money $repayment = null, string $account = 'N')
    {
        $this->participantId = $participantId;
        $this->actionId      = $eventId;
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
            throw new \InvalidArgumentException("Payment attribute account shouldn't be " . $account);
        }
        $this->account = $account;
    }
}
