<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 * akce
 */
class DetailPresenter extends BasePresenter {

    /**
     *
     * @var \Model\ExportService
     */
    protected $exportService;
    
    /**
     *
     * @var \Model\MemberService
     */
    protected $memberService;


    public function __construct(\Model\ExportService $export, \Model\MemberService $member) {
        parent::__construct();
        $this->exportService = $export;
        $this->memberService = $member;
    }
    
    public function renderDefault($aid) {
        $this->template->funkce = $this->isAllowed("EV_EventFunction_ALL_EventCamp") ? $this->campService->event->getFunctions($aid) : false;
        $this->template->accessDetail = $this->isAllowed(self::STable . "_DETAIL");
        $this->template->skautISUrl = $this->userService->getSkautisUrl();

        if (is_array($this->event->ID_UnitArray->string)) {
            $this->template->troops = array_map(function($id) {
                return $this->unitService->getDetail($id);
            }, $this->event->ID_UnitArray->string);
        } elseif (is_string($this->event->ID_UnitArray->string)) {
            $this->template->troops = array($this->unitService->getDetail($this->event->ID_UnitArray->string));
        }
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }

        $form = $this['formEdit'];
        $form->setDefaults(array(
            "aid" => $aid,
            "prefix" => $this->event->prefix,
        ));
    }

    public function renderReport($aid) {
        if (!$this->isAllowed("EV_EventFunction_ALL_EventCamp")) {
            $this->flashMessage("Nemáte právo přistupovat k táboru", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        if (!$this->campService->chits->isConsistent($aid)) {
            $this->flashMessage("Data v účtech a ve skautisu jsou nekonzistentní!", "warning");
            $this->redirect("default", array("aid" => $aid));
        }

        $template = $this->exportService->getCampReport($this->createTemplate(), $aid, $this->campService);
        $this->campService->participants->makePdf($template, "reportCamp.pdf");
        $this->terminate();
    }

    function createComponentFormEdit($name) {
        $form = $this->prepareForm($this, $name);
        $form->addProtection();
        $form->addText("prefix", "Prefix", NULL, 6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditSubmitted(Form $form) {
        if (!$this->isAllowed("EV_EventCamp_DETAIL")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu tábora", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        if ($this->campService->event->updatePrefix($values['aid'], $values['prefix'])) {
            $this->flashMessage("Prefix byl nastaven.");
            //$this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se nastavit prefix.", "danger");
        }
        $this->redirect("this");
    }

    function createComponentFormAddFunction($name) {
        $combo = $this->memberService->getCombobox(FALSE, 18);

        $form = $this->prepareForm($this, $name);
        $form->addSelect("person", NULL, $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setAttribute("class", "combobox");
        $form->addHidden("fid");
        $form->addHidden("aid");
        $form->addSubmit('send', 'Přidat')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

}
