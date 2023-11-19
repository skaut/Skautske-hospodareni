<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\EducationModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use Cake\Chronos\ChronosDate;
use Model\Payment\ReadModel\Queries\EducationsWithoutGroupQuery;

class SelectForGroupPresenter extends BasePresenter
{
    public function actionDefault(): void
    {
        $this->template->setParameters(['educations' => $this->queryBus->handle(new EducationsWithoutGroupQuery(ChronosDate::today()->year))]);
    }
}
