<?php

namespace App\AccountancyModule\PaymentModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter {
    public function actionDefault() {
        $this->redirect("Payment:default");
    }
    
}
