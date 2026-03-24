<?php

declare(strict_types=1);

namespace App\Model\Auth\Resources;

use Nette\StaticClass;

final class Admin
{
    use StaticClass;

    public const ACCESS = [self::class, 'ADMIN_ACCESS'];
}
