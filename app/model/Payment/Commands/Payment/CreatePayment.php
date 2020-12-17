<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Payment;

use Cake\Chronos\Date;
use Model\Common\EmailAddress;
use Model\Payment\Handlers\Payment\CreatePaymentHandler;
use Model\Payment\VariableSymbol;

/**
 * @see CreatePaymentHandler
 */
final class CreatePayment
{
    private int $groupId;

    private string $name;

    /** @var EmailAddress[] */
    private array $recipients;

    private float $amount;

    private Date $dueDate;

    private ?int $personId;

    private ?VariableSymbol $variableSymbol;

    private ?int $constantSymbol;

    private string $note;

    /**
     * @param EmailAddress[] $recipients
     */
    public function __construct(
        int $groupId,
        string $name,
        array $recipients,
        float $amount,
        Date $dueDate,
        ?int $personId,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note
    ) {
        $this->groupId        = $groupId;
        $this->name           = $name;
        $this->recipients     = $recipients;
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

    /** @return EmailAddress[] */
    public function getRecipients() : array
    {
        return $this->recipients;
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
