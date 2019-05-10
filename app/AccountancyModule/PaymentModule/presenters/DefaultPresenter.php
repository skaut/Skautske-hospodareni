<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

class DefaultPresenter extends BasePresenter
{
    public function actionDefault() : void
    {
        $this->redirect('GroupList:');
    }
}
