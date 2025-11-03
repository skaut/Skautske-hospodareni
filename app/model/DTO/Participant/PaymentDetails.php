<?php

declare(strict_types=1);

namespace model\DTO\Participant;

use Cake\Chronos\ChronosDate;
use Nette\SmartObject;

/**
 * @property int              $personId
 * @property string|null      $variableSymbol
 * @property float|null       $price
 * @property string|null      $paymentNote
 * @property int|null         $specificSymbol
 * @property ChronosDate|null $paymentTerm
 */
class PaymentDetails
{
    use SmartObject;

    public function __construct(
        private int $personId,
        private ?string $variableSymbol,
        private ?float $price,
        private ?string $paymentNote,
        private ?int $specificSymbol,
        private ?string $paymentTerm,
    ) {
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }

    public function getVariableSymbol(): ?string
    {
        return $this->variableSymbol;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getPaymentNote(): ?string
    {
        return $this->paymentNote;
    }

    public function getSpecificSymbol(): ?int
    {
        return $this->specificSymbol;
    }

    public function getPaymentTerm(): ?ChronosDate
    {
        // fix weekends - cannot use weekend for due date
        if ($this->paymentTerm !== null) {
            $date = new ChronosDate($this->paymentTerm);

            if ($date->isSaturday() || $date->isSunday()) {
                return $date->modify('next monday');
            }

            return $date;
        }

        return null;
    }
}
