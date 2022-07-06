<?php

namespace App\GaelO\Services\StoreObjects\Export;

class ExportReviewDataCollection
{
    private string $studyName;
    private array $exportReviewDatas = [];

    public function __construct(string $studyName)
    {
        $this->studyName = $studyName;
    }

    private function getExportData(string $visitGroupName, string $visitTypeName): ExportReviewData
    {
        return $this->exportReviewDatas[$visitGroupName . '_' . $visitTypeName];
    }

    private function createExportData(string $visitGroupName, string $visitTypeName): void
    {
        $this->exportReviewDatas[$visitGroupName . '_' . $visitTypeName] = new ExportReviewData($this->studyName, $visitGroupName, $visitTypeName);
    }

    private function isExisitingExportData(string $visitGroupName, string $visitTypeName): bool
    {
        return $this->exportReviewDatas[$visitGroupName . '_' . $visitTypeName] != null;
    }


    public function addData(string $visitGroupName, string $visitTypeName, array $data)
    {
        if (!$this->isExisitingExportData($visitGroupName, $visitTypeName)) {
            $this->createExportData($visitGroupName, $visitTypeName);
        };

        $exportData = $this->getExportData($visitGroupName, $visitTypeName);
        $exportData->addData($data);
    }

    public function getCollection(): array
    {
        return array_values($this->exportReviewDatas);
    }
}
