<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Cake\Chronos\Date;
use Model\Payment\ReadModel\Queries\EducationsWithoutGroupQuery;

class SelectEducationForGroupPresenter extends BasePresenter
{
    public function actionDefault() : void
    {
        $this->template->setParameters(['educations' => $this->queryBus->handle(new EducationsWithoutGroupQuery(Date::today()->year))]);
    }
}
