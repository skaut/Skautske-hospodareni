<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\ReadModel\QueryHandlers\CountAutomaticPairingGroupsQueryHandler;

/**
 * How many groups the cron of automatic pairing works with: open groups with the option switched on
 * whose bank account is connected to FIO. Closed groups and GPC accounts are left out, as in
 * BankService::pairAutomaticGroups().
 *
 * @see CountAutomaticPairingGroupsQueryHandler
 */
final class CountAutomaticPairingGroupsQuery
{
}
