<?php

namespace App\GaelO\Services\StoreObjects\Export;

abstract class ExportDataResults
{

    const EXPORT_TYPE_XLS = 'xlsx';
    const EXPORT_TYPE_CSV = 'csv';

    const EXPORT_TYPE_DICOMS = 'dicoms';
    const EXPORT_TYPE_PATIENTS = 'patients';
    const EXPORT_TYPE_REVIEWS = 'reviews';
    const EXPORT_TYPE_VISITS = 'visits';
    const EXPORT_TYPE_TRACKER = 'tracker';

    private string $exportDataType;

    public function __construct(string $exportDataType)
    {
        $this->exportDataType = $exportDataType;
    }

    public abstract function getXlsExportFile(): ExportFile;
    public abstract function getCsvExportFiles(): array;

    public function getExportDataType()
    {
        return $this->exportDataType;
    }
}
