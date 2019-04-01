<?php

declare(strict_types=1);

namespace Model\Payment;

use Nette\SmartObject;
use function number_format;
use function sprintf;
use function strpos;

/**
 * @property-read int $count
 * @property-read float $amount
 */
class Summary
{
    use SmartObject;

    /** @var int */
    private $count;

    /** @var float */
    private $amount;

    public function __construct(int $count, float $amount)
    {
        $this->count  = $count;
        $this->amount = $amount;
    }

    public function getCount() : int
    {
        return $this->count;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function __toString() : string
    {
        if ($this->amount > 0) {
            $formattedAmount = number_format($this->amount, strpos((string) $this->amount, '.') ? 2 : 0, ',', ' ');
            return sprintf('%s (%d)', $formattedAmount, $this->count);
        }
        return '';
    }
}
