<?php

declare(strict_types=1);

namespace App\Presentation\Payments\CampSelectForGroup;

use App\Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Cake\Chronos\ChronosDate;

use function array_values;

final class CampSelectForGroupPresenter extends BasePresenter
{
    public function renderDefault(): void
    {
        $this->template->setParameters(
            ['camps' => array_values($this->queryBus->handle(new CampsWithoutGroupQuery(ChronosDate::today()->year)))],
        );
    }
}
