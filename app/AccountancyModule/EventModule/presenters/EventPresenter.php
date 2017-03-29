<?php

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\EventModule\Components\Functions;
use App\AccountancyModule\EventModule\Factories\IFunctionsFactory;
use Model\Services\PdfRenderer;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Model\MemberService;
use Model\ExportService;
use Nextras\Forms\Controls\DatePicker;

/**
 * @author Hána František <sinacek@gmail.com>
 * akce
 */
class EventPresenter extends BasePresenter
{

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
        parent::__construct();
        $this->exportService = $exportService;
        $this->memberService = $memberService;
        $this->functionsFactory = $functionsFactory;
        $this->pdf = $pdf;
    }

    public function renderDefault($aid, $funcEdit = FALSE) : void
    {
        if ($aid == NULL) {
            $this->redirect("Default:");
        }
        //nastavení dat do formuláře pro editaci
        $func = FALSE;

        if ($this->isAllowed("EV_EventFunction_ALL_EventGeneral")) {
            $func = $this->eventService->event->getFunctions($aid);
        }

        $accessEditBase = $this->isAllowed("EV_EventGeneral_UPDATE");

        if ($accessEditBase) {
            $form = $this['formEdit'];
            $form->setDefaults([
                "aid" => $aid,
                "name" => $this->event->DisplayName,
                "start" => $this->event->StartDate,
                "end" => $this->event->EndDate,
                "location" => $this->event->Location,
                "type" => $this->event->ID_EventGeneralType,
                "scope" => $this->event->ID_EventGeneralScope,
                "prefix" => $this->event->prefix
            ]);
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
            $this->redrawControl("contentSnip");
        }
    }

    public function handleOpen($aid) : void
    {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Open")) {
            $this->flashMessage("Nemáte právo otevřít akci", "warning");
            $this->redirect("this");
        }
        $this->eventService->event->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("this");
    }

    public function handleClose($aid) : void
    {
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

    public function handleActivateStatistic() : void
    {
        $this->eventService->participants->activateEventStatistic($this->aid);
        //flash message?
        $this->redirect('this', ["aid" => $this->aid]);
    }

    public function actionPrintAll($aid) : void
    {
        $chits = (array)$this->eventService->chits->getAll($this->aid);

        $template = (string)$this->exportService->getEventReport($this->createTemplate(), $aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= (string)$this->exportService->getParticipants($this->createTemplate(), $aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= (string)$this->exportService->getCashbook($this->createTemplate(), $aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= (string)$this->exportService->getChits($this->createTemplate(), $aid, $this->eventService, $chits);

        $this->pdf->render($template, 'all.pdf');
        $this->terminate();
    }

    public function handleRemoveFunction($aid, $fid) : void
    {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Function")) {
            $this->flashMessage("Nemáte oprávnění upravit vedení akce", "danger");
            $this->redirect("this");
        }

        if (!$this->eventService->event->setFunction($this->aid, NULL, $fid)) {
            $this->flashMessage("Funkci se nepodařilo odebrat", "danger");
        }
        $this->redirect("this");
    }

    public function renderReport($aid): void
    {
        if (!$this->isAllowed("EV_EventGeneral_DETAIL")) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("default", ["aid" => $aid]);
        }
        $template = $this->exportService->getEventReport($this->createTemplate(), $aid, $this->eventService);

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    protected function createComponentFormEdit($name) : Form
    {
        $form = $this->prepareForm($this, $name);

        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od")
            ->setRequired('Musíte zadat datum začátku akce');
        $form->addDatePicker("end", "Do")
            ->setRequired('Musíte zadat datum konce akce')
            ->addRule([\MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText("location", "Místo");
        $form->addSelect("type", "Typ (+)", $this->eventService->event->getTypes());
        $form->addSelect("scope", "Rozsah (+)", $this->eventService->event->getScopes());
        $form->addText("prefix", "Prefix")
            ->setMaxLength(6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
            ->setAttribute("class", "btn btn-primary")
            ->onClick[] = function(SubmitButton $button) : void {
                $this->formEditSubmitted($button);
            };

        return $form;
    }

    private function formEditSubmitted(SubmitButton $button) : void
    {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu akce", "danger");
            $this->redirect("this");
        }
        $values = $button->getForm()->getValues(TRUE);
        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        $id = $this->eventService->event->update($values);

        if ($id) {
            $this->flashMessage("Základní údaje byly upraveny.");
            $this->redirect("default", ["aid" => $values['aid']]);
        } else {
            $this->flashMessage("Nepodařilo se upravit základní údaje", "danger");
        }
        $this->redirect("this");
    }

    protected function createComponentFunctions() : Functions
    {
        return $this->functionsFactory->create($this->aid);
    }
}
