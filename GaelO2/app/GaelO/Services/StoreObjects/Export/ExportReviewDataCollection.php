<?php

namespace App\GaelO\Services\StoreObjects\Export;

class ExportReviewDataCollection
{
    private string $studyName;
    private string $role;
    private array $exportReviewDatas = [];

    public function __construct(string $studyName, string $role)
    {
        $this->studyName = $studyName;
        $this->role = $role;
    }

    private function getExportData(string $visitGroupName, string $visitTypeName): ExportReviewData
    {
        return $this->exportReviewDatas[$visitGroupName . '_' . $visitTypeName];
    }

    private function createExportData(string $visitGroupName, string $visitTypeName): void
    {
        $exportReviewData = new ExportReviewData();
        $exportReviewData->setContext($this->studyName, $visitGroupName, $visitTypeName, $this->role);
        $this->exportReviewDatas[$visitGroupName . '_' . $visitTypeName] = $exportReviewData;
    }

    private function isExisitingExportData(string $visitGroupName, string $visitTypeName): bool
    {
        return array_key_exists(($visitGroupName . '_' . $visitTypeName), $this->exportReviewDatas);
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
