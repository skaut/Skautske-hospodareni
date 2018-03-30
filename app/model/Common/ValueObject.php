<?php

declare(strict_types=1);

namespace Model\Common;

interface ValueObject
{

    public function equals(ValueObject $otherValueObject): bool;

}
