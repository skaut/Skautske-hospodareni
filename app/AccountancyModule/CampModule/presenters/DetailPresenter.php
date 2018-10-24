<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\Forms\BaseForm;
use Model\Auth\Resources\Camp;
use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\SkautisCampId;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Model\Unit\UnitNotFound;
use Nette\Application\UI\Form;
use function array_map;
use function count;
use function is_array;
use function is_string;
use function property_exists;

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
        $this->pdf           = $pdf;
    }

    public function renderDefault(int $aid) : void
    {
        $this->template->functions = $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid)
            ? $this->queryBus->handle(new CampFunctions(new SkautisCampId($aid)))
            : null;

        $this->template->accessDetail = $this->authorizator->isAllowed(Camp::ACCESS_DETAIL, $aid);
        $this->template->skautISUrl   = $this->userService->getSkautisUrl();

        if (property_exists($this->event->ID_UnitArray, 'string')) {
            $unitIdOrIds = $this->event->ID_UnitArray->string;

            if (is_array($unitIdOrIds)) {
                $this->template->troops = array_map(
                    function ($id) {
                        try {
                            return $this->unitService->getDetail((int) $id);
                        } catch (UnitNotFound $exc) {
                            return ['ID' => $id, 'DisplayName' => 'Jednotka (' . $id . ') již neexistuje.'];
                        }
                    },
                    $this->event->ID_UnitArray->string
                );
            } elseif (is_string($unitIdOrIds)) {
                $this->template->troops = [$this->unitService->getDetail((int) $unitIdOrIds)];
            }
        } else {
            $this->template->troops = [];
        }

        if ($this->isAjax()) {
            $this->redrawControl('contentSnip');
        }

        $form = $this['formEdit'];
        $form->setDefaults(
            [
            'aid' => $aid,
            'prefix' => $this->event->prefix,
            ]
        );
    }

    public function renderReport(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid)) {
            $this->flashMessage('Nemáte právo přistupovat k táboru', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }

        $template = $this->exportService->getCampReport($aid, $this->eventService, $this->areTotalsConsistentWithSkautis($aid));
        $this->pdf->render($template, 'reportCamp.pdf');
        $this->terminate();
    }

    protected function createComponentFormEdit() : Form
    {
        $form = new BaseForm();
        $form->addText('prefix', 'Prefix')
            ->setMaxLength(6);
        $form->addHidden('aid');
        $form->addSubmit('send', 'Upravit')
            ->setAttribute('class', 'btn btn-primary');
        $form->onSuccess[] = function (Form $form) : void {
            $this->formEditSubmitted($form);
        };
        return $form;
    }

    private function formEditSubmitted(Form $form) : void
    {
        if (! $this->authorizator->isAllowed(Camp::ACCESS_DETAIL, $this->aid)) {
            $this->flashMessage('Nemáte oprávnění pro úpravu tábora', 'danger');
            $this->redirect('this');
        }
        $values     = $form->getValues();
        $campId     = (int) $values['aid'];
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($campId)));

        $this->commandBus->handle(new UpdateChitNumberPrefix($cashbookId, $values['prefix']));
        $this->flashMessage('Prefix byl nastaven.');

        $this->redirect('this');
    }

    private function areTotalsConsistentWithSkautis(int $campId) : bool
    {
        $totals = $this->queryBus->handle(new InconsistentCampCategoryTotalsQuery(new SkautisCampId($campId)));

        return count($totals) === 0;
    }
}
