<?php

namespace App\GaelO\Services\StoreObjects\Export;

class ExportReviewData
{

    private string $studyName;
    private string $visitGroupName;
    private string $visitTypeName;
    private array $data = [];

    public function __construct(string $studyName, string $visitGroupName, string $visitTypeName)
    {
        $this->studyName = $studyName;
        $this->visitGroupName = $visitGroupName;
        $this->visitTypeName = $visitTypeName;
    }

    public function getVisitGroupName(): string
    {
        return $this->visitGroupName;
    }

    public function getVisitTypeName(): string
    {
        return $this->visitTypeName;
    }

    public function getColumns(){
        return [... array_keys($this->data[0]), ... $this->specificColumns];
    }

    public function addData(array $data): void
    {
        $this->data[] = $data;
    }

    public function getData(): array
    {
        $rows = [];
        foreach($this->data as $review){
            $reviewData = $review['review_data'];
            unset($review['review_data']);
            $review['sent_files'] = json_encode($review['sent_files']);
            $rows[] = array_merge($review, $reviewData);
        }

        return $rows;
    }
}
