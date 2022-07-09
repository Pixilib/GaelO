<?php

namespace App\GaelO\Services\StoreObjects\Export;

use Exception;

class ExportReviewResults extends ExportDataResults{

    private ExportFile $xlsExport;
    private Array $CSVExports = [];

    const EXPORT_INVESTIGATOR_FORM = 'InvestigatorsForms';
    const EXPORT_REVIEW_FORM = 'ReviewersForms';

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_REVIEWS);
    }

    public function addExportFile( string $type, string $path, ?string $key=null ){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('export_forms.xlsx', $path);
        }else if ($type === ExportDataResults::EXPORT_TYPE_CSV) {
            $this->CSVExports[] = new ExportFile($key.'.csv', $path);
        }else{
            throw new Exception('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array {
        return [$this->xlsExport];
    }

    public function getCsvExportFiles() : array {
        return [...$this->CSVExports];
    }

    public function getZipExportFiles(): array {
        return [];
    }


}
