<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Payment;

use Cake\Chronos\Date;
use Model\Payment\Handlers\Payment\CreatePaymentHandler;
use Model\Payment\VariableSymbol;

/**
 * @see CreatePaymentHandler
 */
final class CreatePayment
{
    private int $groupId;

    private string $name;

    private ?string $email = null;

    private float $amount;

    private Date $dueDate;

    private ?int $personId = null;

    private ?VariableSymbol $variableSymbol = null;

    private ?int $constantSymbol = null;

    private string $note;

    public function __construct(
        int $groupId,
        string $name,
        ?string $email,
        float $amount,
        Date $dueDate,
        ?int $personId,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note
    ) {
        $this->groupId        = $groupId;
        $this->name           = $name;
        $this->email          = $email;
        $this->amount         = $amount;
        $this->dueDate        = $dueDate;
        $this->personId       = $personId;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note           = $note;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function getAmount() : float
    {
        return $this->amount;
    }

    public function getDueDate() : Date
    {
        return $this->dueDate;
    }

    public function getPersonId() : ?int
    {
        return $this->personId;
    }

    public function getVariableSymbol() : ?VariableSymbol
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    public function getNote() : string
    {
        return $this->note;
    }
}
