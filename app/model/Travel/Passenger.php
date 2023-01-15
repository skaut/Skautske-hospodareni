<?php

declare(strict_types=1);

namespace Model\Travel;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 *
 * @property-read string    $name
 * @property-read string    $contact
 * @property-read string    $address
 * @property-read int|NULL  $contractId
 */
final class Passenger
{
    use SmartObject;

    /** @ORM\Column(type="string", name="driver_name") */
    private string $name;

    /** @ORM\Column(type="string", name="driver_contact") */
    private string $contact;

    /** @ORM\Column(type="string", name="driver_address") */
    private string $address;

    /** @ORM\Column(type="integer", nullable=true) */
    private int|null $contractId = null;

    public function __construct(string $name, string $contact, string $address)
    {
        $this->name    = $name;
        $this->contact = $contact;
        $this->address = $address;
    }

    /**
     * nezbytné pro řazení v Gridu cestovních příkazů
     */
    public function __toString(): string
    {
        return $this->name;
    }

    public static function fromContract(Contract $contract): Passenger
    {
        $contractPassenger = $contract->getPassenger();

        $passenger = new Passenger(
            $contractPassenger->getName(),
            $contractPassenger->getContact(),
            $contractPassenger->getAddress(),
        );
        $passenger->setContractId($contract->getId());

        return $passenger;
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

    public function getContractId(): int|null
    {
        return $this->contractId;
    }
}
