<?php

namespace Model\Services;

class PdfRenderer
{

    /**
     * Renders PDF to output stream
     * @param string $template
     * @param string $filename
     * @param bool $landscape TRUE for landscape, FALSE for portrait mode
     */
    public function render(string $template, string $filename, bool $landscape = FALSE): void
    {
        $mpdf = new \mPDF('utf-8', $landscape ? 'A4-L' : 'A4', 0, '', 10, 10, 10, 10, 9, 9, 'P');

        $mpdf->WriteHTML($template, NULL);
        $mpdf->Output($filename, 'I');
    }

}
