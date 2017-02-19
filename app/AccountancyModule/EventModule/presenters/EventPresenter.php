<?php

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\EventModule\Factories\IFunctionsFactory;
use Model\Services\PdfRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Model\MemberService;
use Model\ExportService;

/**
 * @author Hána František <sinacek@gmail.com> 
 * akce
 */
class EventPresenter extends BasePresenter {
    
    /** @var ExportService */
    protected $exportService;
    
    /** @var MemberService */
    private $memberService;

    /** @var IFunctionsFactory */
    private $functionsFactory;

    /** @var PdfRenderer */
    private $pdf;

    /**
     * EventPresenter constructor.
     * @param ExportService $exportService
     * @param MemberService $memberService
     * @param IFunctionsFactory $functionsFactory
     * @param PdfRenderer $pdf
     */
    public function __construct(
        ExportService $exportService,
        MemberService $memberService,
        IFunctionsFactory $functionsFactory,
        PdfRenderer $pdf
    )
    {
        $this->exportService = $exportService;
        $this->memberService = $memberService;
        $this->functionsFactory = $functionsFactory;
        $this->pdf = $pdf;
    }

    public function renderDefault($aid, $funcEdit = FALSE) {
        if ($aid == NULL) {
            $this->redirect("Default:");
        }
        //nastavení dat do formuláře pro editaci
        $func = false;

        if ($this->isAllowed("EV_EventFunction_ALL_EventGeneral")) {
            $func = $this->eventService->event->getFunctions($aid);
        }

        $accessEditBase = $this->isAllowed("EV_EventGeneral_UPDATE");
//        && $this->isAllowed("EV_EventGeneral_UPDATE_Function");
        if ($accessEditBase) {
            $form = $this['formEdit'];
            $form->setDefaults(array(
                "aid" => $aid,
                "name" => $this->event->DisplayName,
                "start" => $this->event->StartDate,
                "end" => $this->event->EndDate,
                "location" => $this->event->Location,
                "type" => $this->event->ID_EventGeneralType,
                "scope" => $this->event->ID_EventGeneralScope,
                "prefix" => $this->event->prefix
            ));
        }

        $this->template->funkce = $func;
        $this->template->funcEdit = $funcEdit;
        $this->template->statistic = $this->eventService->participants->getEventStatistic($this->aid);
        $this->template->accessFunctionUpdate = $this->isAllowed("EV_EventGeneral_UPDATE_Function");
        $this->template->accessEditBase = $accessEditBase;
        $this->template->accessCloseEvent = $this->isAllowed("EV_EventGeneral_UPDATE_Close");
        $this->template->accessOpenEvent = $this->isAllowed("EV_EventGeneral_UPDATE_Open");
        $this->template->accessDetailEvent = $this->isAllowed("EV_EventGeneral_DETAIL");

        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function handleOpen($aid) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Open")) {
            $this->flashMessage("Nemáte právo otevřít akci", "warning");
            $this->redirect("this");
        }
        $this->eventService->event->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("this");
    }

    public function handleClose($aid) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Close")) {
            $this->flashMessage("Nemáte právo akci uzavřít", "warning");
            $this->redirect("this");
        }
        if ($this->eventService->event->isCloseable($aid)) {
            $this->eventService->event->close($aid);
            $this->flashMessage("Akce byla uzavřena.");
        } else {
            $this->flashMessage("Před uzavřením akce musí být vyplněn vedoucí akce", "danger");
        }
        $this->redirect("this");
    }

    public function handleActivateStatistic() {
        $this->eventService->participants->activateEventStatistic($this->aid);
        //flash message?
        $this->redirect('this', array("aid" => $this->aid));
    }

    public function actionPrintAll($aid) {
        $chits = (array) $this->eventService->chits->getAll($this->aid);

        $template = (string) $this->exportService->getEventReport($this->createTemplate(), $aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= (string) $this->exportService->getParticipants($this->createTemplate(), $aid, $this->eventService) . $this->exportService->getNewPage();
//        $template .= (string)$this->exportService->getHpd($this->createTemplate(), $aid, $this->eventService, $this->unitService) . $this->exportService->getNewPage();
        $template .= (string) $this->exportService->getCashbook($this->createTemplate(), $aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= (string) $this->exportService->getChits($this->createTemplate(), $aid, $this->eventService, $this->unitService, $chits);

        $this->pdf->render($template, 'all.pdf');
        $this->terminate();
    }

    public function handleRemoveFunction($aid, $fid) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Function")) {
            $this->flashMessage("Nemáte oprávnění upravit vedení akce", "danger");
            $this->redirect("this");
        }

        if (!$this->eventService->event->setFunction($this->aid, NULL, $fid)) {
            $this->flashMessage("Funkci se nepodařilo odebrat", "danger");
        }
        $this->redirect("this");
    }

    public function renderReport($aid) {
        if (!$this->isAllowed("EV_EventGeneral_DETAIL")) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        $template = $this->exportService->getEventReport($this->createTemplate(), $aid, $this->eventService);

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    function createComponentFormEdit($name) {
//        $combo = $this->memberService->getCombobox(NULL, TRUE);
        $form = $this->prepareForm($this, $name);
        $form->addProtection();
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od")->addRule(Form::FILLED, "Musíte zadat datum začátku akce");
        $form->addDatePicker("end", "Do")->addRule(Form::FILLED, "Musíte zadat datum konce akce");
        $form->addText("location", "Místo");
        $form->addSelect("type", "Typ (+)", $this->eventService->event->getTypes());
        $form->addSelect("scope", "Rozsah (+)", $this->eventService->event->getScopes());
        $form->addText("prefix", "Prefix")
                ->setMaxLength(6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
                        ->setAttribute("class", "btn btn-primary")
                ->onClick[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditSubmitted(SubmitButton $button) {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu akce", "danger");
            $this->redirect("this");
        }
        $values = $button->getForm()->getValues();
        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        $id = $this->eventService->event->update($values);

        if ($id) {
            $this->flashMessage("Základní údaje byly upraveny.");
            $this->redirect("default", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Nepodařilo se upravit základní údaje", "danger");
        }
        $this->redirect("this");
    }

    protected function createComponentFunctions()
    {
        return $this->functionsFactory->create($this->aid);
    }
}
