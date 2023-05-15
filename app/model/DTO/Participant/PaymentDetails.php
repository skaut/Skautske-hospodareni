<?php

declare(strict_types=1);

namespace model\DTO\Participant;

use Cake\Chronos\Date;
use Nette\SmartObject;

/**
 * @property-read int $personId
 * @property-read string|null $variableSymbol
 * @property-read float|null $price
 * @property-read string|null $paymentNote
 * @property-read string|null $specificSymbol
 * @property-read Date|null $paymentTerm
 */
class PaymentDetails
{
    use SmartObject;

    public function __construct(
        private int $personId,
        private string|null $variableSymbol = null,
        private float|null $price = null,
        private string|null $paymentNote = null,
        private int|null $specificSymbol = null,
        private string|null $paymentTerm = null,
    ) {
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }

    public function getVariableSymbol(): string|null
    {
        return $this->variableSymbol;
    }

    public function getPrice(): float|null
    {
        return $this->price;
    }

    public function getPaymentNote(): string|null
    {
        return $this->paymentNote;
    }

    public function getSpecificSymbol(): int|null
    {
        return $this->specificSymbol;
    }

    public function getPaymentTerm(): Date|null
    {
        return $this->paymentTerm !== null ? new Date($this->paymentTerm) : null;
    }
}
