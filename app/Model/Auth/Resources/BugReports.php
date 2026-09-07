<?php

declare(strict_types=1);

namespace App\Model\Auth\Resources;

use Nette\StaticClass;

final class BugReports
{
    use StaticClass;

    public const ACCESS = [self::class, 'BUG_REPORTS_ACCESS'];
}
