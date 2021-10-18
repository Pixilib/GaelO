<?php

namespace App\GaelO\Services\StoreObjects\Export;

use Exception;

class ExportPatientResults extends ExportDataResults{

    private ExportFile $xlsExport;
    private ExportFile $csvExport;

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_PATIENTS);
    }

    public function addExportFile(string $type, string $path ){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('export_patient.xlsx', $path);
        }else if ($type === ExportDataResults::EXPORT_TYPE_CSV) {
            $this->csvExport = new ExportFile('export_patient.csv', $path);
        }else{
            throw new Exception('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array {
        return [$this->xlsExport];
    }

    public function getCsvExportFiles() : array {
        return [$this->csvExport];
    }

    public function getZipExportFiles(): array
    {
        return [];
    }

}
