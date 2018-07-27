<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

class Person
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string[] */
    private $emails;

    /**
     * @param string[] $emails
     */
    public function __construct(int $id, string $name, array $emails)
    {
        $this->id     = $id;
        $this->name   = $name;
        $this->emails = $emails;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getEmails() : array
    {
        return $this->emails;
    }
}
