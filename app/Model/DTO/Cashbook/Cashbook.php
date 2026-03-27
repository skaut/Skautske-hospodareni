<?php

declare(strict_types=1);

namespace App\Model\DTO\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use Nette\SmartObject;

class Cashbook
{
    use SmartObject;

    public function __construct(
        private CashbookId $id,
        private CashbookType $type,
        private ?string $cashChitNumberPrefix,
        private ?string $bankChitNumberPrefix,
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

    public function getChitNumberPrefix(PaymentMethod $paymentMethod): ?string
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
