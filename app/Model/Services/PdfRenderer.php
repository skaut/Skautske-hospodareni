<?php

declare(strict_types=1);

namespace App\Model\Services;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;

class PdfRenderer
{
    public function __construct(private string $tempDir)
    {
    }

    /**
     * Renders PDF to output stream.
     *
     * @param bool $landscape TRUE for landscape, FALSE for portrait mode
     */
    public function render(string $template, string $filename, bool $landscape = false): void
    {
        $mpdf = $this->createMpdf($landscape);
        $this->writeHtml($mpdf, $template);
        $mpdf->Output($filename, Destination::INLINE);
    }

    public function renderToString(string $template, bool $landscape = false): string
    {
        $mpdf = $this->createMpdf($landscape);
        $this->writeHtml($mpdf, $template);

        return $mpdf->OutputBinaryData();
    }

    private function createMpdf(bool $landscape): Mpdf
    {
        return new Mpdf([
            'format' => $landscape ? 'A4-L' : 'A4',
            'mode' => 'utf-8',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => $this->tempDir,
        ]);
    }

    private function writeHtml(Mpdf $mpdf, string $template): void
    {
        try {
            $mpdf->WriteHTML($template, 0);
        } catch (MpdfException $e) {
            throw new RuntimeException('Failed to render PDF HTML.', 0, $e);
        }
    }
}
