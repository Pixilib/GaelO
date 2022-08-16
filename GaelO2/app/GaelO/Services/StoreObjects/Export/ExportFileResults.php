<?php

namespace App\GaelO\Services\StoreObjects\Export;

use App\GaelO\Exceptions\GaelOException;

class ExportFileResults extends ExportDataResults{

    private ExportFile $zipExport;

    public function __construct()
    {
        parent::__construct(parent::EXPORT_TYPE_FILES);
    }

    public function addExportFile(string $type, string $path ){

        if($type === ExportDataResults::EXPORT_TYPE_ZIP) {
            $this->zipExport = new ExportFile('export_files.zip', $path);
        }else{
            throw new GaelOException('Unknown File Type');
        }

    }

    public function getXlsExportFiles() : array {
        return [];
    }

    public function getCsvExportFiles() : array {
        return [];
    }

    public function getZipExportFiles() : array {
        return [$this->zipExport];
    }

}
