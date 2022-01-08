<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Education;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\SkautisEducationId;
use Model\ExportService;
use Model\Services\PdfRenderer;

use function assert;

class EducationPresenter extends BasePresenter
{
    protected ExportService $exportService;

    private PdfRenderer $pdf;

    public function __construct(
        ExportService $exportService,
        PdfRenderer $pdf
    ) {
        parent::__construct();
        $this->exportService = $exportService;
        $this->pdf           = $pdf;
    }

    public function renderDefault(?int $aid): void
    {
        if ($aid === null) {
            $this->redirect('Default:');
        }

        $cashbook = $this->queryBus->handle(new CashbookQuery($this->getCashbookId($aid)));
        assert($cashbook instanceof Cashbook);

        $this->template->setParameters([
            'accessDetailEvent' => $this->authorizator->isAllowed(Education::ACCESS_DETAIL, $aid),
        ]);

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function renderReport(int $aid): void
    {
        if (! $this->authorizator->isAllowed(Education::ACCESS_DETAIL, $aid)) {
            $this->flashMessage('Nemáte právo přistupovat k akci', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }

        $template = $this->exportService->getEducationReport(new SkautisEducationId($aid));

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    private function getCashbookId(int $skautisEducationId): CashbookId
    {
        return $this->queryBus->handle(new EducationCashbookIdQuery(new SkautisEducationId($skautisEducationId)));
    }
}
