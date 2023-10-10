<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\PdfInterface;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfAdapter implements PdfInterface
{

    public function saveViewToPdf(string $view, array $parameters, string $filename): void
    {
        $pdf = Pdf::loadView('mails.mail_radiomics_report', $parameters)->setOption('defaultFont', 'sans-serif');
        $pdf->save($filename);
    }
}
