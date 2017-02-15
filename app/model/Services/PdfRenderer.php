<?php

namespace Model\Services;

class PdfRenderer
{

    private const ORIENTATION = 'P';
    private const FONT_SIZE = 0;
    private const FONT = '';
    private const MARGIN = 10;
    private const HEADER_MARGIN = 9;
    private const FOOTER_MARGIN = 9;

    /**
     * Renders PDF to output stream
     * @param string $template
     * @param string $filename
     */
    public function render(string $template, string $filename) : void
    {
        $mpdf = new \mPDF(
            'utf-8',
            'A4',
            self::FONT_SIZE,
            self::FONT,
            self::MARGIN,
            self::MARGIN,
            self::MARGIN,
            self::MARGIN,
            self::HEADER_MARGIN,
            self::FOOTER_MARGIN,
            self::ORIENTATION
        );

        $mpdf->WriteHTML($template, NULL);
        $mpdf->Output($filename, 'I');
    }

}
