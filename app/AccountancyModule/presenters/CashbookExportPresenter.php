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
use Model\Cashbook\ReadModel\Queries\CashbookDisplayNameQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CashbookScansQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\ChitScansQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportChits;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\Common\File;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\ExcelService;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use RuntimeException;
use Ublaboo\Responses\PSR7StreamResponse;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;
use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function date;
use function GuzzleHttp\Psr7\stream_for;
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

        $spreadsheet = $this->excelService->getChitsExport(
            CashbookId::fromString($cashbookId),
            $this->getChitsWithIds($chitIds)
        );

        $this->sendResponse(new ExcelResponse('Export-vybranych-paragonu', $spreadsheet));
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
        $method = PaymentMethod::get($paymentMethod);

        $template = $this->exportService->getCashbook(CashbookId::fromString($cashbookId), $method);
        $filename = $method->equals(PaymentMethod::CASH()) ? 'pokladni-kniha' : 'bankovni-transakce';
        $this->pdf->render($template, $filename . '.pdf');

        $this->terminate();
    }

    /**
     * Exports cashbook (list of cashbook operations) as XLS file
     */
    public function actionExportCashbook(string $cashbookId, string $paymentMethod) : void
    {
        $cashbookId = CashbookId::fromString($cashbookId);

        if (! PaymentMethod::isValidValue($paymentMethod)) {
            throw new BadRequestException(
                sprintf('Invalid payment method %s', $paymentMethod),
                IResponse::S400_BAD_REQUEST
            );
        }

        $spreadsheet = $this->excelService->getCashbook($cashbookId, PaymentMethod::get($paymentMethod));
        $this->sendResponse(
            new ExcelResponse(
                sprintf(
                    '%s-pokladni-kniha-%s',
                    Strings::webalize($this->queryBus->handle(new CashbookDisplayNameQuery($cashbookId))),
                    date('Y_n_j')
                ),
                $spreadsheet
            )
        );
    }

    public function actionExportScans(string $cashbookId, string $paymentMethod) : void
    {
        $method = PaymentMethod::get($paymentMethod);

        $files = $this->queryBus->handle(new CashbookScansQuery(CashbookId::fromString($cashbookId), $method));

        $options = new Archive();
        $options->setSendHttpHeaders(true);
        $zip = new ZipStream('Skeny dokladů.zip', $options);

        foreach ($files as $name => $file) {
            assert($file instanceof File);
            $zip->addFileFromPsr7Stream($name, $file->getContents());
        }
        $zip->finish();
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

    public function actionDownloadScan(string $cashbookId, int $chitId, string $path) : void
    {
        $this->downloadScan($cashbookId, $chitId, $path, false);
    }

    public function actionDownloadScanThumbnail(string $cashbookId, int $chitId, string $path) : void
    {
        $this->downloadScan($cashbookId, $chitId, $path, true);
    }

    private function downloadScan(string $cashbookId, int $chitId, string $path, bool $thumbnail) : void
    {
        $cashbookId = CashbookId::fromString($cashbookId);
        foreach ($this->queryBus->handle(new ChitScansQuery($cashbookId, $chitId)) as $scan) {
            assert($scan instanceof File);

            if ($scan->getPath() !== $path) {
                continue;
            }
            $contents = $scan->getContents();
            if ($thumbnail) {
                $image = Image::fromString($contents);
                $image->resize(150, 150);
                $contents = stream_for($image->toString());
            }

            $this->sendResponse(new PSR7StreamResponse($contents, $scan->getFileName()));
        }

        throw new BadRequestException('Scan not found', IResponse::S404_NOT_FOUND);
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
