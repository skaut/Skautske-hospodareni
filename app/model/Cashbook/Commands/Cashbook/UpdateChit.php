<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Handlers\Cashbook\UpdateChitHandler;

/**
 * @see UpdateChitHandler
 */
final class UpdateChit
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var ChitNumber|NULL */
    private $number;

    /** @var Date */
    private $date;

    /** @var Recipient|null */
    private $recipient;

    /** @var Amount */
    private $amount;

    /** @var string */
    private $purpose;

    /** @var int */
    private $categoryId;

    public function __construct(
        CashbookId $cashbookId,
        int $chitId,
        ?ChitNumber $number,
        Date $date,
        ?Recipient $recipient,
        Amount $amount,
        string $purpose,
        int $categoryId
    ) {
        $this->cashbookId = $cashbookId;
        $this->chitId     = $chitId;
        $this->number     = $number;
        $this->date       = $date;
        $this->recipient  = $recipient;
        $this->amount     = $amount;
        $this->purpose    = $purpose;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }

    public function getNumber() : ?ChitNumber
    {
        return $this->number;
    }

    public function getDate() : Date
    {
        return $this->date;
    }

    public function getRecipient() : ?Recipient
    {
        return $this->recipient;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function getCategoryId() : int
    {
        return $this->categoryId;
    }
}
