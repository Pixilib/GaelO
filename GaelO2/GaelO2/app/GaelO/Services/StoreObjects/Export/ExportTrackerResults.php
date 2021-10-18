<?php

namespace App\GaelO\Services\StoreObjects\Export;

use Exception;

class ExportTrackerResults extends ExportDataResults{

    private ExportFile $xlsExport;

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_TRACKER);
    }

    public function addExportFile( string $type, string $path, string $key = null ){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('export_tracker.xlsx', $path);
        }else{
            throw new Exception('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array {
        return [$this->xlsExport];

    }
    public function getCsvExportFiles() : array {
        return [];
    }

    public function getZipExportFiles(): array {
        return [];
    }


}
