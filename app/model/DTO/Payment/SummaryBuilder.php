<?php

namespace Model\DTO\Payment;

use DateTimeImmutable;
use Model\Payment\Payment;
use Model\Payment\Payment\State;

class SummaryBuilder
{

    /** @var DateTimeImmutable */
    private $now;

    /** @var bool */
    private $separateOverdue;

    /** @var float[] */
    private $amounts;

    /** @var int[] */
    private $counts;

    /** @var string[] */
    private $keys = [self::OVERDUE, State::PREPARING, State::SENT, State::COMPLETED];

    private const OVERDUE = "overdue";

    public function __construct(DateTimeImmutable $now, bool $separateOverdue)
    {
        $this->now = $now;

        if($separateOverdue === FALSE) {
            unset($this->keys[0]);
        }

        $this->separateOverdue = $separateOverdue;
        $this->amounts = array_fill_keys($this->keys, 0.0);
        $this->counts = array_fill_keys($this->keys, 0.0);
    }


    public function addPayment(Payment $payment): void
    {
        $state = $payment->getState()->getValue();
        $isOverdue = $this->separateOverdue === TRUE && $payment->getDueDate() < $this->now && $state !== State::PREPARING;
        $key = $isOverdue ? self::OVERDUE : $payment->getState()->getValue();

        if(!isset($this->amounts[$key])) {
            return;
        }

        $this->amounts[$key] += $payment->getAmount();
        $this->counts[$key]++;
    }

    /**
     * @return Summary[]
     */
    public function build(): array
    {
        $summaries = [];

        $totalAmount = array_sum($this->amounts);

        foreach($this->keys as $key) {
            $amount = $this->amounts[$key];
            $count = $this->counts[$key];
            $percentage = $totalAmount !== 0.0 ? $amount / $totalAmount * 100 : 0;
            $summaries[$key] = new Summary($count, $amount, $percentage);
        }

        return $summaries;
    }

}
