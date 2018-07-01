<?php

namespace App\AccountancyModule\PaymentModule;

class DefaultPresenter extends BasePresenter
{

    public function actionDefault() : void
    {
        $this->redirect("Payment:default");
    }

}
