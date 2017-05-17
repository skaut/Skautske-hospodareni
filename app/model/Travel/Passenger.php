<?php

namespace Model\Travel;

use Nette\SmartObject;

/**
 * @property-read string    $name
 * @property-read string    $contact
 * @property-read string    $address
 * @property-read int|NULL  $contractId
 */
final class Passenger
{

    use SmartObject;

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

    public static function fromContract(Contract $contract): Passenger
    {
        $driver = new Passenger(
            $contract->getDriverName(),
            $contract->getDriverContact(),
            $contract->getDriverAddress()
        );
        $driver->setContractId($contract->getId());

        return $driver;
    }

    private function setContractId(int $contractId): void
    {
        $this->contractId = $contractId;
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
