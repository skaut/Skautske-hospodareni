<?php

namespace Model\Payment\Payment;

use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $bankAccount
 */
class Transaction
{

	use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $bankAccount;

    public function __construct(int $id, string $bankAccount)
    {
        $this->id = $id;
        $this->bankAccount = $bankAccount;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBankAccount(): string
    {
        return $this->bankAccount;
    }

}
