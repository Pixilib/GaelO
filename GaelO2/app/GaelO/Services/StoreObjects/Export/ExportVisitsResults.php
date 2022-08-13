<?php

namespace App\GaelO\Services\StoreObjects\Export;

use App\GaelO\Exceptions\GaelOException;

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
            throw new GaelOException('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array{
        return [$this->xlsExport];
    }

    public function getCsvExportFiles() : array {
        return [...$this->csvVisitsExport];
    }

    public function getZipExportFiles(): array {
        return [];
    }
}
