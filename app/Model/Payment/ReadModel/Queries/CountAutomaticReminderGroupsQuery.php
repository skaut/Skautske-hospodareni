<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\ReadModel\QueryHandlers\CountAutomaticReminderGroupsQueryHandler;

/**
 * How many groups the cron of automatic reminders works with: open groups with the reminders switched
 * on, the days chosen, an active template and a sender. Closed groups are left out.
 *
 * @see CountAutomaticReminderGroupsQueryHandler
 */
final class CountAutomaticReminderGroupsQuery
{
}
