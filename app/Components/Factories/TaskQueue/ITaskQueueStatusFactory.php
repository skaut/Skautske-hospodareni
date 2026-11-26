<?php

declare(strict_types=1);

namespace App\Components\Factories\TaskQueue;

use App\Components\TaskQueue\TaskQueueStatus;
use App\Model\TaskQueue\TaskScope;

interface ITaskQueueStatusFactory
{
    /**
     * @param TaskScope $scope     checked by the presenter; the component never shows another scope
     * @param bool      $canManage whether the user may cancel what still waits and hide a finished batch with problems
     */
    public function create(TaskScope $scope, bool $canManage): TaskQueueStatus;
}
