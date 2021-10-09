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

    private CashbookId $id;

    private CashbookType $type;

    private ?string $cashChitNumberPrefix = null;

    private ?string $bankChitNumberPrefix = null;

    private string $note;

    private bool $hasCashOnlyNumericChitNumbers;

    private bool $hasBankOnlyNumericChitNumbers;

    public function __construct(
        CashbookId $id,
        CashbookType $type,
        ?string $cashChitNumberPrefix,
        ?string $bankChitNumberPrefix,
        string $note,
        bool $hasCashOnlyNumericChitNumbers,
        bool $hasBankOnlyNumericChitNumbers
    ) {
        $this->id                            = $id;
        $this->type                          = $type;
        $this->cashChitNumberPrefix          = $cashChitNumberPrefix;
        $this->bankChitNumberPrefix          = $bankChitNumberPrefix;
        $this->note                          = $note;
        $this->hasCashOnlyNumericChitNumbers = $hasCashOnlyNumericChitNumbers;
        $this->hasBankOnlyNumericChitNumbers = $hasBankOnlyNumericChitNumbers;
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
