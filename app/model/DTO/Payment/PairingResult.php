<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\ChronosDate;

use function sprintf;

class PairingResult
{
    public function __construct(private string $accountName, private ChronosDate $since, private ChronosDate $until, private int $count)
    {
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getSince(): ChronosDate
    {
        return $this->since;
    }

    public function getUntil(): ChronosDate
    {
        return $this->until;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getMessage(): string
    {
        if ($this->count > 0) {
            return sprintf(
                'Platby na účtu "%s" byly spárovány (%d) za období %s - %s',
                $this->accountName,
                $this->count,
                $this->since->format('j.n.Y'),
                $this->until->format('j.n.Y'),
            );
        }

        return sprintf(
            'Žádné platby na účtu "%s" nebyly spárovány za období %s - %s',
            $this->accountName,
            $this->since->format('j.n.Y'),
            $this->until->format('j.n.Y'),
        );
    }
}
