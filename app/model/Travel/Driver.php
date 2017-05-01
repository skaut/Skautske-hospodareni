<?php

namespace Model\Travel;

class Driver
{

    /** @var string */
    private $name;

    /** @var string */
    private $contact;

    /** @var string */
    private $address;

    /** @var int|NULL */
    private $contractId;

    public function __construct(string $name, string $contact, string $address)
    {
        $this->name = $name;
        $this->contact = $contact;
        $this->address = $address;
    }

    public static function fromContract(Contract $contract): Driver
    {
        $driver = new Driver(
            $contract->getDriverName(),
            $contract->getDriverContact(),
            $contract->getDriverAddress()
        );
        $driver->contractId = $contract->getId();

        return $driver;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getContractId(): ?int
    {
        return $this->contractId;
    }

}
