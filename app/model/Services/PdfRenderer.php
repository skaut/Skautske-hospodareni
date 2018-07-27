<?php

declare(strict_types=1);

namespace Model\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfRenderer
{
    /** @var string */
    private $tempDir;

    public function __construct(string $tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * Renders PDF to output stream
     * @param bool $landscape TRUE for landscape, FALSE for portrait mode
     */
    public function render(string $template, string $filename, bool $landscape = false) : void
    {
        $mpdf = new Mpdf([
            'format' => $landscape ? 'A4-L' : 'A4',
            'mode' => 'utf-8',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => $this->tempDir,
        ]);

        @$mpdf->WriteHTML($template, 0);
        $mpdf->Output($filename, Destination::INLINE);
    }
}
