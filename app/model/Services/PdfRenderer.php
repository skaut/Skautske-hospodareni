<?php

namespace Model\Services;

class PdfRenderer
{

    /**
     * Renders PDF to output stream
     * @param string $template
     * @param string $filename
     */
    public function render(string $template, string $filename) : void
    {
        $mpdf = new \mPDF('utf-8', 'A4', 0, '',10,10, 10, 10, 9, 9, 'P');

        $mpdf->WriteHTML($template, NULL);
        $mpdf->Output($filename, 'I');
    }

}
