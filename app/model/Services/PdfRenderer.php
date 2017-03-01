<?php

namespace Model\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

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
		$mpdf = new Mpdf([
			'format' => $landscape ? 'A4-L' : 'A4',
			'mode' => 'utf-8',
			'margin_left' => 10,
			'margin_right' => 10,
			'margin_top' => 10,
			'margin_bottom' => 10,
		]);

        $mpdf->WriteHTML($template, NULL);
        $mpdf->Output($filename, Destination::INLINE);
    }

}
