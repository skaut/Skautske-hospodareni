<?php

namespace Model\Bank\Fio;

use Nette;

class Transaction extends Nette\Object
{

    /** @var string */
    private $id;

    /** @var \DateTime */
    private $date;

    /** @var float */
    private $amount;

    /** @var string */
    private $bankAccount;

    /** @var string */
    private $name;

    /** @var int|NULL */
    private $variableSymbol;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string|NULL */
    private $note;

    /**
     * Transaction constructor.
     * @param string $id
     * @param \DateTime $date
     * @param float $amount
     * @param string $bankAccount
     * @param string $name
     * @param int|NULL $variableSymbol
     * @param int|NULL $constantSymbol
     * @param NULL|string $note
     */
    public function __construct(
        $id,
        \DateTime $date,
        $amount,
        $bankAccount,
        $name,
        $variableSymbol,
        $constantSymbol,
        $note)
    {
        $this->id = $id;
        $this->date = $date;
        $this->amount = $amount;
        $this->bankAccount = $bankAccount;
        $this->name = $name;
        $this->variableSymbol = $variableSymbol;
        $this->constantSymbol = $constantSymbol;
        $this->note = $note;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int|NULL
     */
    public function getVariableSymbol()
    {
        return $this->variableSymbol;
    }

    /**
     * @return int|NULL
     */
    public function getConstantSymbol()
    {
        return $this->constantSymbol;
    }

    /**
     * @return NULL|string
     */
    public function getNote()
    {
        return $this->note;
    }

}
