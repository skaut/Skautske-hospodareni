<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\EventModule\Components\MissingAutocomputedCategoryControl;
use App\AccountancyModule\EventModule\Factories\IMissingAutocomputedCategoryControlFactory;
use Model\Auth\Resources\Camp;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CampPragueParticipantsQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Common\UnitId;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\SkautisCampId;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\UnitNotFound;
use function array_filter;
use function array_map;
use function assert;
use function count;

class DetailPresenter extends BasePresenter
{
    /** @var ExportService */
    protected $exportService;

    /** @var PdfRenderer */
    private $pdf;

    /** @var IMissingAutocomputedCategoryControlFactory */
    private $missingAutocomputedCategoryControlFactory;

    public function __construct(
        ExportService $export,
        PdfRenderer $pdf,
        IMissingAutocomputedCategoryControlFactory $missingAutocomputedCategoryControlFactory
    ) {
        parent::__construct();
        $this->exportService                             = $export;
        $this->pdf                                       = $pdf;
        $this->missingAutocomputedCategoryControlFactory = $missingAutocomputedCategoryControlFactory;
    }

    public function renderDefault(int $aid, bool $missingCategories = false) : void
    {
        $troops = array_filter(array_map(
            function (UnitId $id) {
                try {
                    return $this->queryBus->handle(new UnitQuery($id->toInt()));
                } catch (UnitNotFound $exc) {
                    return null;
                }
            },
            $this->event->getParticipatingUnits()
        ));

        if ($this->isAjax()) {
            $this->redrawControl('contentSnip');
        }

        $cashbook = $this->queryBus->handle(new CashbookQuery($this->getCashbookId()));
        assert($cashbook instanceof Cashbook);

        try {
            $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId()));
        } catch (MissingCategory $exc) {
            $finalRealBalance  = null;
            $missingCategories = true;
        }

        $this->template->setParameters([
            'troops' => $troops,
            'skautISUrl'   => $this->userService->getSkautisUrl(),
            'accessDetail' => $this->authorizator->isAllowed(Camp::ACCESS_DETAIL, $aid),
            'functions' => $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid)
                ? $this->queryBus->handle(new CampFunctions(new SkautisCampId($aid)))
                : null,
            'pragueParticipants' => $this->queryBus->handle(new CampPragueParticipantsQuery(
                $this->event->getId(),
                $this->event->getRegistrationNumber(),
                $this->event->getStartDate()
            )),
            'finalRealBalance' => $finalRealBalance,
            'prefix' => $cashbook->getChitNumberPrefix(PaymentMethod::CASH()),
            'missingCategories' => $missingCategories,
        ]);
    }

    public function renderReport(int $aid) : void
    {
        if (! $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid)) {
            $this->flashMessage('Nemáte právo přistupovat k táboru', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }

        try {
            $template = $this->exportService->getCampReport($aid, $this->areTotalsConsistentWithSkautis($aid));
            $this->pdf->render($template, 'reportCamp.pdf');
            $this->terminate();
        } catch (MissingCategory $exc) {
            $this->flashMessage('Chybí základní kategorie. Zapněte si automatické dopočítávání rozpočtu!', 'danger');
            $this->redirect('default', ['aid' => $this->aid, 'missingCategories' => true]);
        }
    }

    private function areTotalsConsistentWithSkautis(int $campId) : bool
    {
        $totals = $this->queryBus->handle(new InconsistentCampCategoryTotalsQuery(new SkautisCampId($campId)));

        return count($totals) === 0;
    }

    private function getCashbookId() : CashbookId
    {
        return $this->queryBus->handle(new CampCashbookIdQuery($this->event->getId()));
    }

    protected function createComponentCategoryAutocomputedControl() : MissingAutocomputedCategoryControl
    {
        return $this->missingAutocomputedCategoryControlFactory->create(new SkautisCampId($this->aid));
    }
}
