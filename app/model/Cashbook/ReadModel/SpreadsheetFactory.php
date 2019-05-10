<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class SpreadsheetFactory
{
    public function create() : Spreadsheet
    {
        $sheet = new Spreadsheet();
        $sheet->getProperties()
            ->setCreator('h.skauting.cz')
            ->setLastModifiedBy('h.skauting.cz');

        return $sheet;
    }
}
