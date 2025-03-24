<?php

namespace App\GaelO\Services\StoreObjects\Export;

use App\GaelO\Exceptions\GaelOException;

class ExportRoleChangesResults extends ExportDataResults{

    private ExportFile $xlsExport;
    private Array $CSVExports = [];

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_ROLES_CHANGES);
    }

    public function addExportFile( string $type, string $path){

        if($type === ExportDataResults::EXPORT_TYPE_XLS) {
            $this->xlsExport = new ExportFile('roles_changes.xlsx', $path);
        }else if ($type === ExportDataResults::EXPORT_TYPE_CSV) {
            $this->CSVExports[] = new ExportFile('roles_changes.csv', $path);
        }else{
            throw new GaelOException('Unknown File Type');
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
