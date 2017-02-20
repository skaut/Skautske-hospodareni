<?php

namespace App\AccountancyModule\UnitAccountModule;

use Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Sinacek\MyRoute;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    /** @persistent */
    public $aid;
    protected $year;
    protected $isReadable;

    protected function startup() : void
    {
        parent::startup();
        $this->isCamp = $this->template->isCamp = FALSE;
        $this->template->aid = $this->aid = (is_null($this->aid) ? $this->unitService->getUnitId() : $this->aid);
        $this->template->year = $this->year = $this->getParameter("year", date("Y"));

        //$this->availableActions = $this->userService->actionVerify("OU_Unit", $this->aid);
        $this->template->isReadable = $this->isReadable = key_exists($this->aid, $this->user->getIdentity()->access['read']);
        $this->template->isEditable = $this->isEditable = key_exists($this->aid, $this->user->getIdentity()->access['edit']);

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro zobrazení stránky", "warning");
            $this->redirect(":Accountancy:Default:", ["aid" => NULL]);
        }
    }

    protected function editableOnly() : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Data jednotky jsou uzavřené a nelze je upravovat.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
    }

}
