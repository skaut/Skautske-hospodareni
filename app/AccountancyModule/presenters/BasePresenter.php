<?php

namespace App\AccountancyModule;

use Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Sinacek\MyRoute;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\BasePresenter
{

    /**
     * backlink
     */
    protected $backlink;

    /**
     * id volane v url, vetsinou id akce
     * @var int
     */
    protected $aid;

    /**
     * je akci možné upravovat?
     * @var bool
     */
    protected $isEditable;

    /**
     * pole dostupných událostí s indexi podle SkautISu
     * @var array
     */
    protected $availableActions;
    public $isCamp;

    protected function startup() : void
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->backlink = $this->storeRequest('+ 3 days');
            if ($this->isAjax()) {
                $this->forward(":Auth:ajax", ["backlink" => $this->backlink]);
            } else {
                $this->redirect(":Default:", ["backlink" => $this->backlink]);
            }
        }
        try {
            $this->userService->updateLogoutTime();
        } catch (\Skautis\Wsdl\AuthenticationException $e) {
            $this->user->logout();
            $this->flashMessage("Vypršelo přihlášení do skautisu.");
            $this->redirect("this");
        }
    }

    protected function beforeRender() : void
    {
        parent::beforeRender();
        $this->template->getLatte()->addFilter(NULL, "\App\AccountancyModule\AccountancyHelpers::loader");
    }

    protected function editableOnly() : void
    {
        throw new NotImplementedException("Implementují jednotlivé moduly");
        //        if (!$this->isEditable) {
        //            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
        //            if($this->isAjax()){
        //                $this->sendPayload();
        //            } else {
        //                $this->redirect("Event:");
        //            }
        //        }
    }

    public function flashMessage($message, $type = 'info') : \stdClass
    {
        $this->redrawControl('flash');
        return parent::flashMessage($message, $type);
    }

    protected function isAllowed($action, $avaibleActions = NULL) : bool
    {
        return array_key_exists($action, $avaibleActions == NULL ? $this->availableActions : $avaibleActions);
    }

}
