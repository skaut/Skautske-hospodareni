<?php

declare(strict_types=1);

namespace Model\DTO\Stat;

use Nette\SmartObject;

/**
 * @property-read int $events
 * @property-read int $camps
 * @property-read int $paymentGroups
 */
final class Counter
{
    use SmartObject;

    public function __construct(private int $events = 0, private int $camps = 0, private int $paymentGroups = 0)
    {
    }

    public function getEvents(): int
    {
        return $this->events;
    }

    public function getCamps(): int
    {
        return $this->camps;
    }

    public function getPaymentGroups(): int
    {
        return $this->paymentGroups;
    }

    public function isEmpty(): bool
    {
        return $this->events === 0 && $this->camps === 0 && $this->paymentGroups === 0;
    }

    public function takeIn(Counter $counter): void
    {
        $this->events        += $counter->getEvents();
        $this->camps         += $counter->getCamps();
        $this->paymentGroups += $counter->getPaymentGroups();
    }
}
