<?php

namespace App\GaelO\Services\StoreObjects\Export;

use Exception;

class ExportReviewResults extends ExportDataResults{

    private ExportFile $xlsExport;
    private ExportFile $investigatorFormCSV;
    private ExportFile $reviewFormCSV;

    const EXPORT_INVESTIGATOR_FORM = 'InvestigatorsForms';
    const EXPORT_REVIEW_FORM = 'ReviewersForms';

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_REVIEWS);
    }

    public function addExportFile( string $type, string $path, string $key ){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('export_forms.xlsx', $path);
        }else if ($type === ExportDataResults::EXPORT_TYPE_CSV) {
            if($key === self::EXPORT_INVESTIGATOR_FORM) $this->investigatorFormCSV = new ExportFile('export_investigator_forms.csv', $path);
            else if($key===self::EXPORT_REVIEW_FORM) $this->reviewFormCSV = new ExportFile('export_review_forms.csv', $path);
            else throw new Exception('Unknown Key Type');
        }else{
            throw new Exception('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array {
        return [$this->xlsExport];
    }

    public function getCsvExportFiles() : array {
        return [$this->investigatorFormCSV, $this->reviewFormCSV];
    }

    public function getZipExportFiles(): array {
        return [];
    }


}
