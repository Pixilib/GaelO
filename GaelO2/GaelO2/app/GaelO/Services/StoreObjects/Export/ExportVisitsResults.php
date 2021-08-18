<?php

namespace App\GaelO\Services\StoreObjects\Export;

use Exception;

class ExportVisitsResults extends ExportDataResults{

    private ExportFile $xlsExport;
    private array $csvVisitsExport = [];

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_VISITS);
    }

    public function addExportFile(string $type, string $path, string $key = null ){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('export_visits.xlsx', $path);
        }else if ($type === ExportDataResults::EXPORT_TYPE_CSV) {
           $this->csvVisitsExport[] = new ExportFile('export_visits_'.$key.'.csv', $path);
        }else{
            throw new Exception('Unknown File Type');
        }

    }

    public function getXlsExportFile() : ExportFile{
        return $this->xlsExport;

    }
    public function getCsvExportFiles() : array {
        return $this->csvVisitsExport;

    }
}
