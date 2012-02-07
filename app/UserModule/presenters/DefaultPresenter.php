<?php

class User_DefaultPresenter extends User_BasePresenter {

    protected function startup() {
        parent::startup();
        $this->model = new UserService();
    }

    function renderDefault($role = NULL, $count=15) {

        if (!$this->presenter->user->isAllowed('user', 'view')) {
            $this->presenter->flashMessage("Nemáte oprávnění pro tuto akci", "fail");
            $this->redirect("Auth:", $this->getApplication()->storeRequest());
        }
        $dataSource = $this->model->getList($role);
        $paginator = $this['vp']->paginator;
        $paginator->itemsPerPage = $count;
        $paginator->itemCount = count($dataSource);
        $dataSource->applyLimit($paginator->length, $paginator->offset);
        $this->template->users = $dataSource->fetchAll();
        $this->template->usersColumns = $dataSource->getResult()->getInfo()->getColumnNames();
    }

    function renderAccess() {
        $modelR = new AclModel();
        $this->template->resources = $modelR->getResources();
        $this->template->priviligies = $modelR->getPrivileges();
    }

}