<?php

namespace App\AccountancyModule\CampModule;

use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 * akce
 */
class DetailPresenter extends BasePresenter {

    /** @var ExportService */
    protected $exportService;
    
    /** @var MemberService */
    protected $memberService;

    /** @var PdfRenderer */
    private $pdf;

    public function __construct(ExportService $export, MemberService $member, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->exportService = $export;
        $this->memberService = $member;
        $this->pdf = $pdf;
    }
    
    public function renderDefault($aid) {
        $this->template->funkce = $this->isAllowed("EV_EventFunction_ALL_EventCamp") ? $this->eventService->event->getFunctions($aid) : false;
        $this->template->accessDetail = $this->isAllowed(self::STable . "_DETAIL");
        $this->template->skautISUrl = $this->userService->getSkautisUrl();
        
        if(property_exists($this->event->ID_UnitArray, "string")){
            if (is_array($this->event->ID_UnitArray->string)) {
                $this->template->troops = array_map(function($id) {
                    return $this->unitService->getDetail($id);
                }, $this->event->ID_UnitArray->string);
            } elseif (is_string($this->event->ID_UnitArray->string)) {
                $this->template->troops = array($this->unitService->getDetail($this->event->ID_UnitArray->string));
            }
        } else {
            $this->template->troops = array();
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
        if (!$this->eventService->chits->isConsistent($aid)) {
            $this->flashMessage("Data v účtech a ve skautisu jsou nekonzistentní!", "warning");
            $this->redirect("default", array("aid" => $aid));
        }

        $template = $this->exportService->getCampReport($this->createTemplate(), $aid, $this->eventService);
        $this->pdf->render($template, 'reportCamp.pdf');
        $this->terminate();
    }

    function createComponentFormEdit($name) {
        $form = $this->prepareForm($this, $name);
        $form->addProtection();
        $form->addText("prefix", "Prefix")
                ->setMaxLength(6);
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

        if ($this->eventService->event->updatePrefix($values['aid'], $values['prefix'])) {
            $this->flashMessage("Prefix byl nastaven.");
            //$this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se nastavit prefix.", "danger");
        }
        $this->redirect("this");
    }

}
