<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Event;
use Model\Auth\Resources\Unit;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportChits;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\EventEntity;
use Model\ExcelService;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function in_array;
use function sprintf;

class CashbookExportPresenter extends BasePresenter
{
    /**
     * @var string
     * @persistent
     */
    public $cashbookId = ''; // default value type is used for type casting

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
        $this->excelService  = $excelService;
        $this->pdf           = $pdf;
    }

    /**
     * @throws SkautisMaintenance
     * @throws BadRequestException
     */
    public function startup() : void
    {
        parent::startup();

        if (! $this->hasAccessToCashbook()) {
            throw new BadRequestException('User has no access to cashbook', IResponse::S403_FORBIDDEN);
        }
    }

    /**
     * Exports selected chits as PDF for printing
     *
     * @param int[] $chitIds
     */
    public function actionPrintChits(string $cashbookId, array $chitIds) : void
    {
        $chitIds  = array_map('\intval', $chitIds);
        $template = $this->queryBus->handle(ExportChits::withChitIds(CashbookId::fromString($cashbookId), $chitIds));
        $this->pdf->render($template, 'paragony.pdf');
        $this->terminate();
    }

    /**
     * Exports selected chits as XLS file
     *
     * @param int[] $chitIds
     */
    public function actionExportChits(string $cashbookId, array $chitIds) : void
    {
        $chitIds = array_map('\intval', $chitIds);

        $this->excelService->getChitsExport(
            CashbookId::fromString($cashbookId),
            $this->getChitsWithIds($chitIds)
        );

        $this->terminate();
    }

    /**
     * Exports all chits as PDF for printing
     */
    public function actionPrintAllChits(string $cashbookId) : void
    {
        $template = $this->exportService->getChitlist(CashbookId::fromString($cashbookId));
        $this->pdf->render($template, 'seznam-dokladu.pdf');
        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) as PDF for printing
     */
    public function actionPrintCashbook(string $cashbookId, string $paymentMethod) : void
    {
        $cashbookName = $this->getEventEntity()->getEvent()->get($this->getSkautisId())->DisplayName;
        $method       = PaymentMethod::get($paymentMethod);

        $template = $this->exportService->getCashbook(CashbookId::fromString($cashbookId), $cashbookName, $method);
        $filename = $method->equals(PaymentMethod::CASH()) ? 'pokladni-kniha' : 'bankovni-transakce';
        $this->pdf->render($template, $filename . '.pdf');

        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) as XLS file
     */
    public function actionExportCashbook(string $cashbookId, string $paymentMethod) : void
    {
        $skautisId = $this->getSkautisId();
        $event     = $this->getEventEntity()->getEvent()->get($skautisId);

        if (! PaymentMethod::isValidValue($paymentMethod)) {
            throw new BadRequestException(
                sprintf('Invalid payment method %s', $paymentMethod),
                IResponse::S400_BAD_REQUEST
            );
        }

        $this->excelService->getCashbook(
            $event->DisplayName,
            CashbookId::fromString($cashbookId),
            PaymentMethod::get($paymentMethod)
        );
        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) with category columns as XLS file
     */
    public function actionExportCashbookWithCategories(string $cashbookId, string $paymentMethod) : void
    {
        if (! PaymentMethod::isValidValue($paymentMethod)) {
            throw new BadRequestException(
                sprintf('Invalid payment method %s', $paymentMethod),
                IResponse::S400_BAD_REQUEST
            );
        }

        $spreadsheet = $this->excelService->getCashbookWithCategories(
            CashbookId::fromString($cashbookId),
            PaymentMethod::get($paymentMethod)
        );

        $this->sendResponse(new ExcelResponse('pokladni-kniha', $spreadsheet));
    }

    /**
     * @throws BadRequestException
     */
    private function hasAccessToCashbook() : bool
    {
        $skautisType = $this->getSkautisType()->getValue();

        $requiredPermissions = [
            ObjectType::EVENT => Event::ACCESS_DETAIL,
            ObjectType::CAMP => Camp::ACCESS_DETAIL,
            ObjectType::UNIT => Unit::ACCESS_DETAIL,
        ];

        if (! isset($requiredPermissions[$skautisType])) {
            throw new RuntimeException('Unknown cashbook type');
        }

        return $this->authorizator->isAllowed($requiredPermissions[$skautisType], $this->getSkautisId());
    }

    /**
     * @throws BadRequestException
     */
    private function getSkautisType() : ObjectType
    {
        try {
            $cashbook = $this->queryBus->handle(new CashbookQuery(CashbookId::fromString($this->cashbookId)));

            assert($cashbook instanceof Cashbook);

            return $cashbook->getType()->getSkautisObjectType();
        } catch (CashbookNotFound $e) {
            throw new BadRequestException($e->getMessage(), IResponse::S404_NOT_FOUND, $e);
        }
    }

    private function getSkautisId() : int
    {
        return $this->queryBus->handle(
            new SkautisIdQuery(CashbookId::fromString($this->cashbookId))
        );
    }

    private function getEventEntity() : EventEntity
    {
        $type = $this->getSkautisType()->getValue();

        if ($type === ObjectType::UNIT) {
            $serviceName = 'unitAccountService';
        } else {
            $serviceName = ($type === ObjectType::EVENT ? 'event' : $type) . 'Service';
        }

        return $this->context->getService($serviceName);
    }

    /**
     * @param int[] $ids
     *
     * @return Chit[]
     */
    private function getChitsWithIds(array $ids) : array
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), CashbookId::fromString($this->cashbookId)));

        $filteredChits = array_filter(
            $chits,
            function (Chit $chit) use ($ids) : bool {
                return in_array($chit->getId(), $ids, true);
            }
        );

        return array_values($filteredChits);
    }
}
