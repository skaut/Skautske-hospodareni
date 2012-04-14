<?php

class DefaultPresenter extends BasePresenter {
    protected function startup() {
        parent::startup();
        if($this->user->isLoggedIn())
            $this->redirect("Accountancy:Default:");
    }

}
