<?php

declare(strict_types=1);

namespace App\Presentation\Payments\EducationSelectForGroup;

use App\Model\Event\ReadModel\Queries\EducationListQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Cake\Chronos\ChronosDate;

final class EducationSelectForGroupPresenter extends BasePresenter
{
    public function actionDefault(): void
    {
        $this->template->setParameters(['educations' => $this->queryBus->handle(new EducationListQuery(ChronosDate::today()->year)) + $this->queryBus->handle(new EducationListQuery(ChronosDate::today()->year + 1))]);
    }
}
