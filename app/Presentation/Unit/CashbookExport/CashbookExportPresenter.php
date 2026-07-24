<?php

declare(strict_types=1);

namespace App\Presentation\Unit\CashbookExport;

use App\BaseSectionPresenter;
use App\Http\ExcelResponse;
use App\Model\Auth\Resources\Camp;
use App\Model\Auth\Resources\Education;
use App\Model\Auth\Resources\Event;
use App\Model\Auth\Resources\Unit;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\CashbookDisplayNameQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookScansQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitScansQuery;
use App\Model\Cashbook\ReadModel\Queries\Pdf\ExportChits;
use App\Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use App\Model\Common\File;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\Chit;
use App\Model\Excel\ExcelService;
use App\Model\Export\ExportService;
use App\Model\Services\PdfRenderer;
use App\SkautisMaintenance;
use Contributte\Application\Response\PSR7StreamResponse;
use GuzzleHttp\Psr7\Utils;
use LogicException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use RuntimeException;
use ZipStream\ZipStream;

use function array_filter;
use function array_map;
use function array_values;
use function date;
use function in_array;
use function sprintf;

final class CashbookExportPresenter extends BaseSectionPresenter
{
    #[Persistent]
    public string $cashbookId = '';

    public function __construct(private ExportService $exportService, private ExcelService $excelService, private PdfRenderer $pdf)
    {
        parent::__construct();
    }

    /**
     * @throws SkautisMaintenance
     * @throws BadRequestException
     */
    public function startup(): void
    {
        parent::startup();

        if (! $this->hasAccessToCashbook()) {
            throw new BadRequestException('User has no access to cashbook', IResponse::S403_Forbidden);
        }
    }

    /**
     * @param int[] $chitIds
     */
    public function actionPrintChits(string $cashbookId, array $chitIds): void
    {
        $chitIds = array_map('\intval', $chitIds);
        $template = $this->queryBus->handle(ExportChits::withChitIds(CashbookId::fromString($cashbookId), $chitIds));
        $this->pdf->render($template, 'paragony.pdf');
        $this->terminate();
    }

    /**
     * @param int[] $chitIds
     */
    public function actionExportChits(string $cashbookId, array $chitIds): void
    {
        $chitIds = array_map('\intval', $chitIds);

        $spreadsheet = $this->excelService->getChitsExport(
            $this->getChitsWithIds($chitIds),
        );

        $this->sendResponse(new ExcelResponse('Export-vybranych-paragonu', $spreadsheet));
    }

    public function actionPrintAllChits(string $cashbookId): void
    {
        $template = $this->exportService->getChitlist(CashbookId::fromString($cashbookId));
        $this->pdf->render($template, 'seznam-dokladu.pdf');
        $this->terminate();
    }

    public function actionPrintCashbook(string $cashbookId, string $paymentMethod): void
    {
        $method = PaymentMethod::get($paymentMethod);

        $template = $this->exportService->getCashbook(CashbookId::fromString($cashbookId), $method);
        $filename = $method->equals(PaymentMethod::CASH()) ? 'pokladni-kniha' : 'bankovni-transakce';
        $this->pdf->render($template, $filename.'.pdf');

        $this->terminate();
    }

    public function actionExportCashbook(string $cashbookId, string $paymentMethod): void
    {
        $cashbookId = CashbookId::fromString($cashbookId);

        if (! PaymentMethod::isValidValue($paymentMethod)) {
            throw new BadRequestException(sprintf('Invalid payment method %s', $paymentMethod), IResponse::S400_BadRequest);
        }

        $spreadsheet = $this->excelService->getCashbook($cashbookId, PaymentMethod::get($paymentMethod));

        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::get($paymentMethod), $cashbookId));
        $spreadsheet->createSheet(1);
        $spreadsheet->setActiveSheetIndex(1);
        $this->excelService->addItemsExport($spreadsheet, $chits);

        $this->sendResponse(
            new ExcelResponse(
                sprintf(
                    '%s-pokladni-kniha-%s',
                    Strings::webalize($this->queryBus->handle(new CashbookDisplayNameQuery($cashbookId))),
                    date('Y_n_j'),
                ),
                $spreadsheet,
            ),
        );
    }

    public function actionExportScans(string $cashbookId, string $paymentMethod): void
    {
        $method = PaymentMethod::get($paymentMethod);

        $files = $this->queryBus->handle(new CashbookScansQuery(CashbookId::fromString($cashbookId), $method));

        $zip = new ZipStream(outputName: 'Skeny dokladů.zip');

        foreach ($files as $name => $file) {
            if (! $file instanceof File) {
                throw new LogicException('Assertion failed.');
            }
            $zip->addFileFromPsr7Stream($name, $file->getContents());
        }

        $zip->finish();
    }

    public function actionExportCashbookWithCategories(string $cashbookId, string $paymentMethod): void
    {
        if (! PaymentMethod::isValidValue($paymentMethod)) {
            throw new BadRequestException(sprintf('Invalid payment method %s', $paymentMethod), IResponse::S400_BadRequest);
        }

        $spreadsheet = $this->excelService->getCashbookWithCategories(
            CashbookId::fromString($cashbookId),
            PaymentMethod::get($paymentMethod),
        );

        $this->sendResponse(new ExcelResponse('pokladni-kniha', $spreadsheet));
    }

    public function actionExportCashbookItems(string $cashbookId, string $paymentMethod): void
    {
        if (! PaymentMethod::isValidValue($paymentMethod)) {
            throw new BadRequestException(sprintf('Invalid payment method %s', $paymentMethod), IResponse::S400_BadRequest);
        }

        $spreadsheet = $this->excelService->getCashbookItems(
            CashbookId::fromString($cashbookId),
            PaymentMethod::get($paymentMethod),
        );

        $this->sendResponse(new ExcelResponse('seznam-polozek', $spreadsheet));
    }

    public function actionDownloadScan(string $cashbookId, int $chitId, string $path): void
    {
        $this->downloadScan($cashbookId, $chitId, $path, false);
    }

    public function actionDownloadScanThumbnail(string $cashbookId, int $chitId, string $path): void
    {
        $this->downloadScan($cashbookId, $chitId, $path, true);
    }

    private function downloadScan(string $cashbookId, int $chitId, string $path, bool $thumbnail): void
    {
        $cashbookId = CashbookId::fromString($cashbookId);
        foreach ($this->queryBus->handle(new ChitScansQuery($cashbookId, $chitId)) as $scan) {
            if (! $scan instanceof File) {
                throw new LogicException('Assertion failed.');
            }
            if ($scan->getPath() !== $path) {
                continue;
            }

            $contents = $scan->getContents();
            if ($thumbnail) {
                $image = Image::fromString($contents->getContents());
                $image->resize(150, 150);
                $contents = Utils::streamFor($image->toString());
            }

            $this->sendResponse(new PSR7StreamResponse($contents, $scan->getFileName()));
        }

        throw new BadRequestException('Scan not found', IResponse::S404_NotFound);
    }

    /** @throws BadRequestException */
    private function hasAccessToCashbook(): bool
    {
        $skautisType = $this->getSkautisType()->getValue();

        $requiredPermissions = [
            ObjectType::EVENT => Event::ACCESS_DETAIL,
            ObjectType::CAMP => Camp::ACCESS_DETAIL,
            ObjectType::UNIT => Unit::ACCESS_DETAIL,
            ObjectType::EDUCATION => Education::ACCESS_DETAIL,
        ];

        if (! isset($requiredPermissions[$skautisType])) {
            throw new RuntimeException('Unknown cashbook type');
        }

        return $this->authorizator->isAllowed($requiredPermissions[$skautisType], $this->getSkautisId());
    }

    /** @throws BadRequestException */
    private function getSkautisType(): ObjectType
    {
        try {
            $cashbook = $this->queryBus->handle(new CashbookQuery(CashbookId::fromString($this->cashbookId)));

            if (! $cashbook instanceof Cashbook) {
                throw new LogicException('Assertion failed.');
            }

            return $cashbook->getType()->getSkautisObjectType();
        } catch (CashbookNotFound $e) {
            throw new BadRequestException($e->getMessage(), IResponse::S404_NotFound, $e);
        }
    }

    private function getSkautisId(): int
    {
        return $this->queryBus->handle(
            new SkautisIdQuery(CashbookId::fromString($this->cashbookId)),
        );
    }

    /**
     * @param int[] $ids
     *
     * @return Chit[]
     */
    private function getChitsWithIds(array $ids): array
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), CashbookId::fromString($this->cashbookId)));

        $filteredChits = array_filter(
            $chits,
            function (Chit $chit) use ($ids): bool {
                return in_array($chit->getId(), $ids, true);
            },
        );

        return array_values($filteredChits);
    }
}
