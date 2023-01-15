<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\PaymentMethod;
use Nette\SmartObject;

class Cashbook
{
    use SmartObject;

    public function __construct(
        private CashbookId $id,
        private CashbookType $type,
        private string|null $cashChitNumberPrefix = null,
        private string|null $bankChitNumberPrefix = null,
        private string $note,
        private bool $hasCashOnlyNumericChitNumbers,
        private bool $hasBankOnlyNumericChitNumbers,
    ) {
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getType(): CashbookType
    {
        return $this->type;
    }

    public function getChitNumberPrefix(PaymentMethod $paymentMethod): string|null
    {
        return $paymentMethod->equals(PaymentMethod::CASH()) ? $this->cashChitNumberPrefix : $this->bankChitNumberPrefix;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function hasOnlyNumericChitNumbers(PaymentMethod $paymentMethod): bool
    {
        return $paymentMethod->equals(PaymentMethod::CASH()) ? $this->hasCashOnlyNumericChitNumbers : $this->hasBankOnlyNumericChitNumbers;
    }
}
