<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\EducationModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use Cake\Chronos\ChronosDate;
use Model\Event\ReadModel\Queries\EducationListQuery;

class SelectForGroupPresenter extends BasePresenter
{
    public function actionDefault(): void
    {
        $this->template->setParameters(['educations' => $this->queryBus->handle(new EducationListQuery(ChronosDate::today()->year))]);
    }
}
