<?php


namespace Model\Payment;


class UnitResolverStub implements IUnitResolver
{

    /** @var int */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getOfficialUnitId(int $unitId): int
    {
        return $this->id;
    }

}
