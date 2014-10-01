<?php

namespace App\AccountancyModule\UnitAccountModule;

use Nette\Caching\Cache,
    Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ChitPresenter extends BasePresenter {

    public $chits;
    public $info;

    /** @persistent */
    public $onlyUnlocked = 1;

    protected function startup() {
        parent::startup();
        $this->template->onlyUnlocked = $this->onlyUnlocked;
    }

    public function actionDefault($year = NULL) {
        $this->chits = array();
        $cache = new Cache($this->context->cacheStorage);

        $cacheKey = __METHOD__;
        
        if (($this->info = $cache->load($cacheKey)) === NULL) {
            $this->info = array();
            $this->info['unit'] = $this->context->unitService->getAllUnder($this->aid);
            $this->info['event'] = $this->context->eventService->event->getAll($this->year);
            $this->info['camp'] = $this->context->campService->event->getAll($this->year);
            $cache->save($cacheKey, $this->info, array(Cache::EXPIRE => '10 minutes'));
        }
        
        $categories = $this->context->budgetService->getCategoriesLeaf($this->aid);
        if(empty($categories['in']) && empty($categories['out'])){
            $this->template->disableForm = TRUE;
        }
    }

    public function renderDefault($year = NULL) {
        $this->getAllChitsByObjectId("unit", $this->chits, $this->onlyUnlocked, $this->context->unitAccountService);
        $this->getAllChitsByObjectId("event", $this->chits, $this->onlyUnlocked, $this->context->eventService);
        $this->getAllChitsByObjectId("camp", $this->chits, $this->onlyUnlocked, $this->context->campService);
        
        $this->template->chitsArr = $this->chits;
        $this->template->info = $this->info;
    }

    protected function getAllChitsByObjectId($objectType, &$chits, $onlyUnlocked, $service) {
        if (in_array($objectType, array("event", "camp"))) {//filtrování akcí spojených pouze s danou jednotkou
            $ids = array();
            foreach ($this->info[$objectType] as $k=>$e) {
                if(array_key_exists($e->ID_Unit, $this->info['unit'])){
                    $ids[] = $k;
                }
            }
        } else {
            $ids = array_keys($this->info[$objectType]);
        }
        foreach ($ids as $oid) {
            $arr = $service->chits->getAll($oid, $onlyUnlocked);
            if (empty($arr)) {
                continue;
            }
            $chits[$objectType][$oid] = $service->chits->getAll($oid, $onlyUnlocked);
        }
    }

    public function handleLock($oid, $id, $type, $act = "lock") {
        if (!in_array($type, array("event", "camp", "unit"))) {
            $this->flashMessage("Neplatný přístup!", "danger");
        } else {
            $service = $this->context->{($type == "unit" ? "unitAccount" : $type) . "Service"};
            $chit = $service->chits->get($id);
            if (!$this->accessChitControl($chit, $service, $type)) {
                $this->flashMessage("Neplatný přístup!", "danger");
            } elseif (in_array($act, array("lock", "unlock"))) {
                $service->chits->{$act}($oid, $id, $this->user->id);
            }
        }

        if ($this->isAjax) {
            $this->redrawControl('flash');
            $this->redrawControl('tableChits');
            $this->redrawControl();
        } else {
            $this->redirect("default");
        }
    }

    protected function createComponentBudgetCategoryForm($name) {
        $categories = $this->context->budgetService->getCategoriesLeaf($this->aid);
        $form = new Form($this, $name);
        foreach ($this->chits as $chType) {
            foreach ($chType as $chGrp) {
                foreach ($chGrp as $ch) {
                    $form->addSelect("selectBudget_in_" . $ch->id, NULL, $categories['in'])
                            ->setPrompt("")
                            ->setDefaultValue($ch->budgetCategoryIn)
                            ->getControlPrototype()->setClass("input-medium");
                    $form->addSelect("selectBudget_out_" . $ch->id, NULL, $categories['out'])
                            ->setPrompt("")
                            ->setDefaultValue($ch->budgetCategoryOut)
                            ->getControlPrototype()->setClass("input-medium");
                }
            }
        }

        $form->onSubmit[] = array($this, $name . 'Submitted');
        $form->addSubmit("send", "Uložit kategorie")
                ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    public function budgetCategoryFormSubmitted(Form $form) {
        $v = $form->values;
        foreach ($this->chits as $chType) {
            foreach ($chType as $chGrp) {
                foreach ($chGrp as $ch) {
                    $this->context->eventService->chits->setBudgetCategories($ch->id, $v['selectBudget_in_' . $ch->id], $v['selectBudget_out_' . $ch->id]); //zde může být libovolný EntityService
                }
            }
        }

        $this->flashMessage("Kategorie byly upraveny.");
        $this->redirect("this");
    }

    protected function accessChitControl($chit, $service, $type) {
        //dump($type);dump($chit);die();
        return array_key_exists($service->chits->getSkautisId($chit->eventId), $this->info[$type]);
        //return array_key_exists($chit->id, $this->chits[$type][$this->context->{$type . "Service"}->chits->getSkautisId($chit->eventId)]);
    }

}
