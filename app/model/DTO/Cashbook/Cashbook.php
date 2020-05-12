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

    /** @var CashbookId */
    private $id;

    /** @var CashbookType */
    private $type;

    /** @var string|NULL */
    private $cashChitNumberPrefix;

    /** @var string|NULL */
    private $bankChitNumberPrefix;

    /** @var string */
    private $note;

    /** @var bool */
    private $hasOnlyNumericChitNumbers;

    public function __construct(
        CashbookId $id,
        CashbookType $type,
        ?string $cashChitNumberPrefix,
        ?string $bankChitNumberPrefix,
        string $note,
        bool $hasOnlyNumericChitNumbers
    ) {
        $this->id                        = $id;
        $this->type                      = $type;
        $this->cashChitNumberPrefix      = $cashChitNumberPrefix;
        $this->bankChitNumberPrefix      = $bankChitNumberPrefix;
        $this->note                      = $note;
        $this->hasOnlyNumericChitNumbers = $hasOnlyNumericChitNumbers;
    }

    public function getId() : string
    {
        return $this->id->toString();
    }

    public function getType() : CashbookType
    {
        return $this->type;
    }

    public function getChitNumberPrefix(PaymentMethod $paymentMethod) : ?string
    {
        return $paymentMethod->equals(PaymentMethod::CASH()) ? $this->cashChitNumberPrefix : $this->bankChitNumberPrefix;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function hasOnlyNumericChitNumbers() : bool
    {
        return $this->hasOnlyNumericChitNumbers;
    }
}
