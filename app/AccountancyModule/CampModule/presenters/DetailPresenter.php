<?php

namespace App\AccountancyModule\CampModule;

use App\Forms\BaseForm;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 * akce
 */
class DetailPresenter extends BasePresenter
{

    /** @var ExportService */
    protected $exportService;

    /** @var PdfRenderer */
    private $pdf;

    public function __construct(ExportService $export, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->exportService = $export;
        $this->pdf = $pdf;
    }

    public function renderDefault(int $aid) : void
    {
        $this->template->funkce = $this->isAllowed("EV_EventFunction_ALL_EventCamp") ? $this->eventService->event->getFunctions($aid) : FALSE;
        $this->template->accessDetail = $this->isAllowed(self::STable . "_DETAIL");
        $this->template->skautISUrl = $this->userService->getSkautisUrl();

        if (property_exists($this->event->ID_UnitArray, "string")) {
            $unitIdOrIds = $this->event->ID_UnitArray->string;

            if (is_array($unitIdOrIds)) {
                $this->template->troops = array_map(function ($id) {
                    return $this->unitService->getDetail($id);
                }, $this->event->ID_UnitArray->string);

            } elseif (is_string($unitIdOrIds)) {
                $this->template->troops = [$this->unitService->getDetail((int)$unitIdOrIds)];
            }
        } else {
            $this->template->troops = [];
        }

        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }

        $form = $this['formEdit'];
        $form->setDefaults([
            "aid" => $aid,
            "prefix" => $this->event->prefix,
        ]);
    }

    public function renderReport($aid) : void
    {
        if (!$this->isAllowed("EV_EventFunction_ALL_EventCamp")) {
            $this->flashMessage("Nemáte právo přistupovat k táboru", "warning");
            $this->redirect("default", ["aid" => $aid]);
        }
        if (!$this->eventService->chits->isConsistent($aid)) {
            $this->flashMessage("Data v účtech a ve skautisu jsou nekonzistentní!", "warning");
            $this->redirect("default", ["aid" => $aid]);
        }

        $template = $this->exportService->getCampReport($aid, $this->eventService);
        $this->pdf->render($template, 'reportCamp.pdf');
        $this->terminate();
    }

    protected function createComponentFormEdit($name) : Form
    {
        $form = new BaseForm();
        $form->addText("prefix", "Prefix")
            ->setMaxLength(6);
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
            ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = function(Form $form) : void {
            $this->formEditSubmitted($form);
        };
        return $form;
    }

    private function formEditSubmitted(Form $form) : void
    {
        if (!$this->isAllowed("EV_EventCamp_DETAIL")) {
            $this->flashMessage("Nemáte oprávnění pro úpravu tábora", "danger");
            $this->redirect("this");
        }
        $values = $form->getValues();

        if ($this->eventService->event->updatePrefix($values['aid'], $values['prefix'])) {
            $this->flashMessage("Prefix byl nastaven.");

        } else {
            $this->flashMessage("Nepodařilo se nastavit prefix.", "danger");
        }
        $this->redirect("this");
    }

}
