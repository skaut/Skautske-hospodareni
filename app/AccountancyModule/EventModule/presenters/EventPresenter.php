<?php

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\EventModule\Components\FunctionsControl;
use App\AccountancyModule\EventModule\Factories\IFunctionsControlFactory;
use Model\Event\Commands\Event\ActivateStatistics;
use Model\Event\Commands\Event\CloseEvent;
use Model\Event\Commands\Event\OpenEvent;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\ReadModel\Queries\EventScopes;
use Model\Event\SkautisEventId;
use Model\ExportService;
use Model\Logger\Log\Type;
use Model\LoggerService;
use Model\MemberService;
use App\Forms\BaseForm;
use Model\Services\PdfRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

class EventPresenter extends BasePresenter
{

    /** @var ExportService */
    protected $exportService;

    /** @var MemberService */
    private $memberService;

    /** @var IFunctionsControlFactory */
    private $functionsFactory;

    /** @var PdfRenderer */
    private $pdf;

    /** @var LoggerService */
    private $loggerService;

    public function __construct(
        ExportService $exportService,
        MemberService $memberService,
        IFunctionsControlFactory $functionsFactory,
        PdfRenderer $pdf,
        LoggerService $loggerService
    )
    {
        parent::__construct();
        $this->exportService = $exportService;
        $this->memberService = $memberService;
        $this->functionsFactory = $functionsFactory;
        $this->pdf = $pdf;
        $this->loggerService = $loggerService;
    }

    public function renderDefault(int $aid): void
    {
        if ($aid == NULL) {
            $this->redirect("Default:");
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

        $this->template->statistic = $this->eventService->participants->getEventStatistic($this->aid);
        $this->template->accessEditBase = $accessEditBase;
        $this->template->accessCloseEvent = $this->isAllowed("EV_EventGeneral_UPDATE_Close");
        $this->template->accessOpenEvent = $this->isAllowed("EV_EventGeneral_UPDATE_Open");
        $this->template->accessDetailEvent = $this->isAllowed("EV_EventGeneral_DETAIL");

        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }
    }

    public function renderLogs(int $aid): void
    {
        $this->template->logs = $this->loggerService->findAllByTypeId(Type::get(Type::OBJECT), $this->event->localId);
    }

    public function handleOpen(int $aid): void
    {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Open")) {
            $this->flashMessage("Nemáte právo otevřít akci", "warning");
            $this->redirect("this");
        }

        $this->commandBus->handle(new OpenEvent($aid));

        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("this");
    }

    public function handleClose(int $aid): void
    {
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Close")) {
            $this->flashMessage("Nemáte právo akci uzavřít", "warning");
            $this->redirect("this");
        }

        /** @var Functions $functions */
        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));

        if ($functions->getLeader() !== NULL) {
            $this->commandBus->handle(new CloseEvent($aid));
            $this->flashMessage("Akce byla uzavřena.");
        } else {
            $this->flashMessage("Před uzavřením akce musí být vyplněn vedoucí akce", "danger");
        }
        $this->redirect("this");
    }

    public function handleActivateStatistic(): void
    {
        $this->commandBus->handle(new ActivateStatistics($this->aid));
        //flash message?
        $this->redirect('this', ["aid" => $this->aid]);
    }

    public function actionPrintAll(int $aid): void
    {
        $chits = $this->eventService->chits->getAll($this->aid);

        $template = $this->exportService->getEventReport($aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= $this->exportService->getParticipants($aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= $this->exportService->getCashbook($aid, $this->eventService) . $this->exportService->getNewPage();
        $template .= $this->exportService->getChits($aid, $this->eventService, $chits);

        $this->pdf->render($template, 'all.pdf');
        $this->terminate();
    }

    public function renderReport(int $aid): void
    {
        if (!$this->isAllowed("EV_EventGeneral_DETAIL")) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("default", ["aid" => $aid]);
        }
        $template = $this->exportService->getEventReport($aid, $this->eventService);

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    protected function createComponentFormEdit() : Form
    {
        $form = new BaseForm();

        $form->addText("name", "Název akce")
            ->setRequired('Musíte zadat název akce');
        $form->addDatePicker("start", "Od")
            ->setRequired('Musíte zadat datum začátku akce');
        $form->addDatePicker("end", "Do")
            ->setRequired('Musíte zadat datum konce akce')
            ->addRule([\MyValidators::class, 'isValidRange'], 'Konec akce musí být po začátku akce', $form['start']);
        $form->addText("location", "Místo");
        $form->addSelect("type", "Typ (+)", $this->eventService->event->getTypes());
        $form->addSelect("scope", "Rozsah (+)", $this->queryBus->handle(new EventScopes()));
        $form->addText("prefix", "Prefix")
            ->setMaxLength(6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
            ->setAttribute("class", "btn btn-primary")
            ->onClick[] = function (SubmitButton $button): void {
                $this->formEditSubmitted($button);
            };

        return $form;
    }

    private function formEditSubmitted(SubmitButton $button): void
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

    protected function createComponentFunctions(): FunctionsControl
    {
        return $this->functionsFactory->create($this->aid);
    }
}
