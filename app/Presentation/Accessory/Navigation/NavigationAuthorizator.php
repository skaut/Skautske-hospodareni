<?php

declare(strict_types=1);

namespace App\Presentation\Accessory\Navigation;

use App\Model\Auth\IAuthorizator as ApplicationAuthorizator;
use App\Model\Auth\Resources\Admin;
use App\Model\Auth\Resources\InvoiceAccess;
use Contributte\MenuControl\IMenuItem;
use Contributte\MenuControl\Security\IAuthorizator;

final class NavigationAuthorizator implements IAuthorizator
{
    public function __construct(private ApplicationAuthorizator $authorizator)
    {
    }

    public function isMenuItemAllowed(IMenuItem $item): bool
    {
        $requiresAdmin = (bool) $item->getData('requiresAdmin', false);
        $requiresInvoiceAccess = (bool) $item->getData('requiresInvoiceAccess', false);

        if ($requiresAdmin && ! $this->authorizator->isAllowed(Admin::ACCESS, null)) {
            return false;
        }

        return ! $requiresInvoiceAccess || $this->authorizator->isAllowed(InvoiceAccess::ACCESS, null);
    }
}
