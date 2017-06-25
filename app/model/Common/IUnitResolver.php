<?php

namespace Model\Payment;

interface IUnitResolver
{

    public function getOfficialUnitId(int $unitId): int;

}
