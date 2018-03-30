<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Event;
use Model\Auth\Resources\Unit;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookTypeQuery;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\EventEntity;
use Model\ExcelService;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class CashbookExportPresenter extends BasePresenter
{

    /**
     * @var int
     * @persistent
     */
    public $cashbookId = 0; // default value type is used for type casting

    /** @var ExportService */
    private $exportService;

    /** @var ExcelService */
    private $excelService;

    /** @var PdfRenderer */
    private $pdf;

    public function __construct(ExportService $exportService, ExcelService $excelService, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->exportService = $exportService;
        $this->excelService = $excelService;
        $this->pdf = $pdf;
    }

    /**
     * @throws SkautisMaintenanceException
     * @throws BadRequestException
     */
    public function startup(): void
    {
        parent::startup();

        if ( ! $this->hasAccessToCashbook()) {
            throw new BadRequestException('User has no access to cashbook', IResponse::S403_FORBIDDEN);
        }
    }

    /**
     * Exports selected chits as PDF for printing
     *
     * @param int[] $chitIds
     */
    public function actionPrintChits(int $cashbookId, array $chitIds): void
    {
        $skautisId = $this->getSkautisId();
        $eventEntity = $this->getEventEntity();

        $chits = $eventEntity->chits->getIn($skautisId, $chitIds);
        $template = $this->exportService->getChits($skautisId, $eventEntity, $chits);
        $this->pdf->render($template, 'paragony.pdf');
        $this->terminate();
    }

    /**
     * Exports selected chits as XLS file
     *
     * @param int[] $chitIds
     */
    public function actionExportChits(int $cashbookId, array $chitIds): void
    {
        $chits = $this->getEventEntity()->chits->getIn($this->getSkautisId(), $chitIds);
        $this->excelService->getChitsExport($chits);
        $this->terminate();
    }

    /**
     * Exports all chits as PDF for printing
     */
    public function actionPrintAllChits(int $cashbookId): void
    {
        $template = $this->exportService->getChitlist($this->getSkautisId(), $this->getEventEntity());
        $this->pdf->render($template, 'seznam-dokladu.pdf');
        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) as PDF for printing
     */
    public function actionPrintCashbook(int $cashbookId): void
    {
        $cashbookName = $this->getEventEntity()->event->get($this->getSkautisId())->DisplayName;

        $template = $this->exportService->getCashbook(CashbookId::fromInt($cashbookId), $cashbookName);
        $this->pdf->render($template, 'pokladni-kniha.pdf');

        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) as XLS file
     */
    public function actionExportCashbook(int $cashbookId): void
    {
        $skautisId = $this->getSkautisId();
        $eventEntity = $this->getEventEntity();
        $event = $this->getEventEntity()->event->get($skautisId);

        $this->excelService->getCashbook($eventEntity, $event);
        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) with category columns as XLS file
     */
    public function actionExportCashbookWithCategories(int $cashbookId): void
    {
        $spreadsheet = $this->excelService->getCashbookWithCategories(CashbookId::fromInt($cashbookId));

        $this->sendResponse(new ExcelResponse('pokladni-kniha', $spreadsheet));
    }

    /**
     * @throws BadRequestException
     */
    private function hasAccessToCashbook(): bool
    {
        $skautisType = $this->getSkautisType()->getValue();

        $requiredPermissions = [
            ObjectType::EVENT => Event::ACCESS_DETAIL,
            ObjectType::CAMP => Camp::ACCESS_DETAIL,
            ObjectType::UNIT => Unit::EDIT, // TODO: add some better permission
        ];

        if ( ! isset($requiredPermissions[$skautisType])) {
            throw new \RuntimeException('Unknown cashbook type');
        }

        return $this->authorizator->isAllowed($requiredPermissions[$skautisType], $this->getSkautisId());
    }

    /**
     * @throws BadRequestException
     */
    private function getSkautisType(): ObjectType
    {
        try {
            /** @var CashbookType $cashbookType */
            $cashbookType = $this->queryBus->handle(
                new CashbookTypeQuery(CashbookId::fromInt($this->cashbookId))
            );

            return $cashbookType->getSkautisObjectType();
        } catch (CashbookNotFoundException $e) {
            throw new BadRequestException($e->getMessage(), IResponse::S404_NOT_FOUND, $e);
        }
    }

    private function getSkautisId(): int
    {
        return $this->queryBus->handle(
            new SkautisIdQuery(CashbookId::fromInt($this->cashbookId))
        );
    }

    private function getEventEntity(): EventEntity
    {
        $type = $this->getSkautisType()->getValue();

        if ($type === ObjectType::UNIT) {
            $serviceName = 'unitAccountService';
        } else {
            $serviceName = ($type === ObjectType::EVENT ? 'event' : $type) . 'Service';
        }

        return $this->context->getService($serviceName);
    }

}
