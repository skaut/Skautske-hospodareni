<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\SkautisCampId;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Model\Unit\UnitNotFound;
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
        $troops = [];
        if (property_exists($this->event->ID_UnitArray, 'string')) {
            $unitIdOrIds = $this->event->ID_UnitArray->string;

            if (is_array($unitIdOrIds)) {
                $troops = array_map(
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
                try {
                    $troops = [$this->unitService->getDetail((int) $unitIdOrIds)];
                } catch (UnitNotFound $exc) {
                    $troops = [];
                }
            }
        }

        if ($this->isAjax()) {
            $this->redrawControl('contentSnip');
        }

        $this->template->setParameters([
            'troops' => $troops,
            'skautISUrl'   => $this->userService->getSkautisUrl(),
            'accessDetail' => $this->authorizator->isAllowed(Camp::ACCESS_DETAIL, $aid),
            'functions' => $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid)
                ? $this->queryBus->handle(new CampFunctions(new SkautisCampId($aid)))
                : null,
            'pragueParticipants' => $this->eventService->getParticipants()->countPragueParticipants($this->event),
        ]);
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

    private function areTotalsConsistentWithSkautis(int $campId) : bool
    {
        $totals = $this->queryBus->handle(new InconsistentCampCategoryTotalsQuery(new SkautisCampId($campId)));

        return count($totals) === 0;
    }
}
