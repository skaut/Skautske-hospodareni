<?php

declare(strict_types=1);

namespace App\Presentation\Payments\EventSelectForGroup;

use App\Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Cake\Chronos\ChronosDate;

use function array_values;

final class EventSelectForGroupPresenter extends BasePresenter
{
    public function renderDefault(): void
    {
        $this->template->setParameters(
            ['events' => array_values($this->queryBus->handle(new EventsWithoutGroupQuery(ChronosDate::today()->year)))],
        );
    }
}
