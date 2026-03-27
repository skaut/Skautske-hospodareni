<?php

declare(strict_types=1);

namespace App\Model\Auth;

use App\Model\Admin\Services\AdminAccessChecker;
use App\Model\Auth\Resources\Admin;
use App\Model\Skautis\Auth\SkautisAuthorizator;
use InvalidArgumentException;

use function count;

final class CompositeAuthorizator implements IAuthorizator
{
    public function __construct(
        private SkautisAuthorizator $skautisAuthorizator,
        private AdminAccessChecker $adminAccessChecker,
    ) {
    }

    /** @param string[] $action */
    public function isAllowed(array $action, ?int $resourceId): bool
    {
        if (count($action) !== 2) {
            throw new InvalidArgumentException('Unknown action');
        }

        if ($action === Admin::ACCESS) {
            return $this->adminAccessChecker->isCurrentUserAllowed();
        }

        return $this->skautisAuthorizator->isAllowed($action, $resourceId);
    }
}
