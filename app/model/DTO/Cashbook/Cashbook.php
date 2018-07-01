<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;

class Cashbook
{
    /** @var CashbookId */
    private $id;

    /** @var CashbookType */
    private $type;

    /** @var string|NULL */
    private $chit_number_prefix;

    /** @var string */
    private $note;

    public function __construct(
        CashbookId $id,
        CashbookType $type,
        ?string $chit_number_prefix,
        string $note
    )
    {
        $this->id = $id;
        $this->type = $type;
        $this->chit_number_prefix = $chit_number_prefix;
        $this->note = $note;
    }

    public function getId(): int
    {
        return $this->id->toInt();
    }

    public function getType(): CashbookType
    {
        return $this->type;
    }

    public function getChitNumberPrefix(): ?string
    {
        return $this->chit_number_prefix;
    }

    public function getNote(): string
    {
        return $this->note;
    }

}
