<?php

namespace App\GaelO\Services\StoreObjects\Export;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\FormService\FormService;

class ExportReviewData
{

    private FormService $formService;
    private string $studyName;
    private string $visitGroupName;
    private string $visitTypeName;
    private array $specificColumnNames;
    private array $data = [];

    public function __construct()
    {
        $this->formService = FrameworkAdapter::make(FormService::class);
    }

    public function setContext(string $studyName, string $visitGroupName, string $visitTypeName, string $role)
    {
        $this->studyName = $studyName;
        $this->visitGroupName = $visitGroupName;
        $this->visitTypeName = $visitTypeName;
        $this->role = $role;
        $studyRules = $this->formService->getSpecificStudiesRules($studyName, $visitGroupName, $visitTypeName);
        if ($role === Constants::ROLE_INVESTIGATOR) $this->specificColumnNames = $studyRules->getInvestigatorInputNames();
        if ($role === Constants::ROLE_REVIEWER) $this->specificColumnNames= $studyRules->getReviewerInputNames();
    }

    public function getVisitGroupName(): string
    {
        return $this->visitGroupName;
    }

    public function getVisitTypeName(): string
    {
        return $this->visitTypeName;
    }

    public function getColumns()
    {
        return [...array_keys($this->data[0]), ...$this->specificColumns];
    }

    public function addData(array $data): void
    {
        $this->data[] = $data;
    }

    public function getData(): array
    {
        $rows = [];
        foreach ($this->data as $review) {
            $reviewEntity = $review;
            $reviewEntity['sent_files'] = json_encode($review['sent_files']);
            $reviewData = [];
            foreach($this->specificColumnNames as $name){
                $reviewData[$name] = $reviewEntity['review_data'][$name] ?? null;
            }
            unset($reviewEntity['review_data']);
            $rows[] = [
                ...$reviewEntity,
                ...$reviewData
            ];
        }

        return $rows;
    }
}
