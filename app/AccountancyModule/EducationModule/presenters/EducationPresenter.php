<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Education;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\ReadModel\Queries\EducationFunctions;
use Model\Event\SkautisEducationId;
use Model\ExportService;
use Model\Services\PdfRenderer;

use function assert;

class EducationPresenter extends BasePresenter
{
    public function __construct(
        protected ExportService $exportService,
        private PdfRenderer $pdf,
    ) {
        parent::__construct();
    }

    public function renderDefault(int|null $aid): void
    {
        if ($aid === null) {
            $this->redirect('Default:');
        }

        $cashbook = $this->queryBus->handle(new CashbookQuery($this->getCashbookId($aid)));
        assert($cashbook instanceof Cashbook);

        try {
            $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId($aid)));
        } catch (MissingCategory) {
            $finalRealBalance  = null;
        }

        $this->template->setParameters([
            'skautISUrl'       => $this->userService->getSkautisUrl(),
            'accessDetail'     => $this->authorizator->isAllowed(Education::ACCESS_DETAIL, $aid),
            'functions' => $this->authorizator->isAllowed(Education::ACCESS_FUNCTIONS, $aid)
                ? $this->queryBus->handle(new EducationFunctions(new SkautisEducationId($aid)))
                : null,
            'finalRealBalance' => $finalRealBalance,
            'prefixCash'           => $cashbook->getChitNumberPrefix(PaymentMethod::CASH()),
            'prefixBank'           => $cashbook->getChitNumberPrefix(PaymentMethod::BANK()),
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
