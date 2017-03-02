<?php
/**
 * Created by PhpStorm.
 * User: fmasa
 * Date: 21.2.17
 * Time: 23:27
 */

namespace Model\DTO\Payment;

class Group
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var string */
    private $name;

    /** @var float|NULL */
    private $defaultAmount;

    /** @var \DateTimeImmutable|NULL */
    private $dueDate;

    /** @var int|NULL */
    private $constantSymbol;

    /** @var string */
    private $emailTemplate;

    /** @var int|NULL */
    private $smtpId;

    /**
     * Group constructor.
     * @param int $id
     * @param int $unitId
     * @param string $name
     * @param float|NULL $defaultAmount
     * @param \DateTimeImmutable|NULL $dueDate
     * @param int|NULL $constantSymbol
     * @param string $emailTemplate
     * @param int|NULL $smtpId
     */
    public function __construct(
        int $id,
        int $unitId,
        string $name,
        ?float $defaultAmount,
        ?\DateTimeImmutable $dueDate,
        ?int $constantSymbol,
        string $emailTemplate,
        ?int $smtpId)
    {
        $this->id = $id;
        $this->unitId = $unitId;
        $this->name = $name;
        $this->defaultAmount = $defaultAmount;
        $this->dueDate = $dueDate;
        $this->constantSymbol = $constantSymbol;
        $this->emailTemplate = $emailTemplate;
        $this->smtpId = $smtpId;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUnitId(): int
    {
        return $this->unitId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float|NULL
     */
    public function getDefaultAmount() : ?float
    {
        return $this->defaultAmount;
    }

    /**
     * @return \DateTimeImmutable|NULL
     */
    public function getDueDate() : ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * @return int|NULL
     */
    public function getConstantSymbol() : ?int
    {
        return $this->constantSymbol;
    }

    /**
     * @return string
     */
    public function getEmailTemplate(): string
    {
        return $this->emailTemplate;
    }

    /**
     * @return string
     */
    public function getSmtpId(): ?int
    {
        return $this->smtpId;
    }

}
