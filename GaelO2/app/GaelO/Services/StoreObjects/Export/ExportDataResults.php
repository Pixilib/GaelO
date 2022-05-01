<?php

namespace App\GaelO\Services\StoreObjects\Export;

abstract class ExportDataResults
{

    const EXPORT_TYPE_XLS = 'xlsx';
    const EXPORT_TYPE_CSV = 'csv';
    const EXPORT_TYPE_ZIP = 'zip';

    const EXPORT_TYPE_DICOMS = 'dicoms';
    const EXPORT_TYPE_PATIENTS = 'patients';
    const EXPORT_TYPE_REVIEWS = 'reviews';
    const EXPORT_TYPE_VISITS = 'visits';
    const EXPORT_TYPE_TRACKER = 'tracker';
    const EXPORT_TYPE_USERS = 'users';
    const EXPORT_TYPE_FILES = 'files';

    private string $exportDataType;

    public function __construct(string $exportDataType)
    {
        $this->exportDataType = $exportDataType;
    }

    public abstract function getXlsExportFiles(): array;
    public abstract function getCsvExportFiles(): array;
    public abstract function getZipExportFiles(): array;

    public function getExportDataType()
    {
        return $this->exportDataType;
    }
}
