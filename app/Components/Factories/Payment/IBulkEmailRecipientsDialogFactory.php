<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\BulkEmailRecipientsDialog;

interface IBulkEmailRecipientsDialogFactory
{
    public function create(int $groupId): BulkEmailRecipientsDialog;
}
