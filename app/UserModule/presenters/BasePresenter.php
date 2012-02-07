<?php
class User_BasePresenter extends BasePresenter {


    protected function startup() {
        parent::startup();

        if (!$this->user->isAllowed("user", "view")) {
            $this->accessFail();
        }
    }

}
