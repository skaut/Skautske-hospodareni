<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Nette;
use Nette\Application\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function gmdate;
use function sprintf;

final class ExcelResponse implements Response
{
    public function __construct(private string $filename, private Spreadsheet $spreadsheet)
    {
    }

    /**
     * Redirect output to a client’s web browser (Excel 2007)
     */
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $httpResponse->addHeader('Content-Disposition', sprintf('attachment;filename="%s.xlsx"', $this->filename));
        $httpResponse->setHeader('Cache-Control', 'max-age=0');

        // If you're serving to IE over SSL, then the following may be needed
        $httpResponse->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        $httpResponse->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        $httpResponse->setHeader('Cache-Control', 'cache, must-revalidate'); // HTTP/1.1
        $httpResponse->setHeader('Pragma', 'public'); // HTTP/1.0

        $xls = new Xlsx($this->spreadsheet);
        $xls->setPreCalculateFormulas(true);
        $xls->save('php://output');
    }
}
