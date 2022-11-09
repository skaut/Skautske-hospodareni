<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\CampModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use Cake\Chronos\Date;
use Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;

use function array_values;

final class SelectForGroupPresenter extends BasePresenter
{
    public function renderDefault(): void
    {
        $this->template->setParameters(
            ['camps' => array_values($this->queryBus->handle(new CampsWithoutGroupQuery(Date::today()->year)))],
        );
    }
}
