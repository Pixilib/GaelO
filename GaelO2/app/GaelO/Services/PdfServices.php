<?php

namespace App\GaelO\Services;

use App\GaelO\Interfaces\Adapters\PdfInterface;

class PdfServices
{

    private PdfInterface $pdfInterface;

    public function __construct(PdfInterface $pdfInterface)
    {
        $this->pdfInterface = $pdfInterface;
    }

    public function saveRadiomicsPdf(string $studyName, string $patientCode, string $visitType, string $visitDate, string $imagePath, array $stats): string
    {
        $parameters = [
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'studyName' => $studyName,
            'visitDate' => $visitDate,
            'image_path' => [$imagePath],
            'stats' => $stats
        ];

        $pdfReportTempFile  = tempnam(ini_get('upload_tmp_dir'), 'TMP_pdf_');
        $this->pdfInterface->saveViewToPdf('mails.mail_radiomics_report', $parameters, $pdfReportTempFile);

        return $pdfReportTempFile;
    }
}
