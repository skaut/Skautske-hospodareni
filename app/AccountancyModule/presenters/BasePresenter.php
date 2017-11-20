<?php

namespace App\AccountancyModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
abstract class BasePresenter extends \App\BasePresenter
{

    /**
     * backlink
     */
    protected $backlink;

    /**
     * id volane v url, vetsinou id akce
     * @var int|mixed
     */
    protected $aid;

    /**
     * je akci možné upravovat?
     * @var bool
     */
    protected $isEditable;

    /**
     * @deprecated Use Authorizator::isAllowed()
     * pole dostupných událostí s indexi podle SkautISu
     * @var array
     */
    protected $availableActions = [];

    /** @var string camp, event, unit */
    public $type;

    protected function startup() : void
    {
        parent::startup();

        if($this->aid != NULL) { // Persistent parameters aren't auto-casted to int
            $this->aid = (int)$this->aid;
        }

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


    public function flashMessage($message, $type = 'info') : \stdClass
    {
        $this->redrawControl('flash');
        return parent::flashMessage($message, $type);
    }

    public function isAllowed($action, $avaibleActions = NULL) : bool
    {
        return array_key_exists($action, $avaibleActions == NULL ? $this->availableActions : $avaibleActions);
    }

    /**
     * Returns current unit ID (e.g oddíl)
     * @return int
     */
    public function getCurrentUnitId(): int
    {
        return $this->aid;
    }

}
