<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\PdfInterface;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfAdapter implements PdfInterface
{

    public function saveViewToPdf(string $view, array $parameters, string $filename): void
    {
        $pdfReportTempFile  = tempnam(ini_get('upload_tmp_dir'), 'TMP_pdf_');
        $pdf = Pdf::loadView('mails.mail_radiomics_report', $parameters);
        $pdf->save($pdfReportTempFile);
    }
}
