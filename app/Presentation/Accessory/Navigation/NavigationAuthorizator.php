<?php

declare(strict_types=1);

namespace App\Presentation\Accessory\Navigation;

use App\Model\Auth\IAuthorizator as ApplicationAuthorizator;
use App\Model\Auth\Resources\Admin;
use App\Model\Auth\Resources\BugReports;
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
        $requiresAdmin = (bool) $item->getDataItem('requiresAdmin', false);
        $requiresAdminArea = (bool) $item->getDataItem('requiresAdminArea', false);
        $requiresBugReportsAccess = (bool) $item->getDataItem('requiresBugReportsAccess', false);
        $requiresAnnouncementsAccess = (bool) $item->getDataItem('requiresAnnouncementsAccess', false);
        $requiresInvoiceAccess = (bool) $item->getDataItem('requiresInvoiceAccess', false);

        if ($requiresAdmin && ! $this->authorizator->isAllowed(Admin::ACCESS, null)) {
            return false;
        }

        if ($requiresAdminArea && ! $this->authorizator->isAllowed(Admin::ANY_ACCESS, null)) {
            return false;
        }

        if ($requiresBugReportsAccess && ! $this->authorizator->isAllowed(BugReports::ACCESS, null)) {
            return false;
        }

        if ($requiresAnnouncementsAccess && ! $this->authorizator->isAllowed(Admin::ANNOUNCEMENTS_ACCESS, null)) {
            return false;
        }

        return ! $requiresInvoiceAccess || $this->authorizator->isAllowed(InvoiceAccess::ACCESS, null);
    }
}
