<?php

namespace App\Jobs\QcReport;

class VisitReport
{
    private string $studyName;
    private string $visitName;
    private string $patientCode;

    private string $visitDate;
    private string $minVisitDate;
    private string $maxVisitDate;
    private string $registrationDate;
    private array $investigatorForm = [];

    private array $seriesReports = [];

    public function setSeriesReports(array $seriesReports)
    {
        $this->seriesReports = $seriesReports;
    }

    public function getSeriesReports(): array
    {
        return $this->seriesReports;
    }

    public function setStudyName(string $studyName)
    {
        $this->studyName = $studyName;
    }

    public function setPatientCode(string $patientCode)
    {
        $this->patientCode = $patientCode;
    }

    public function setVisitName(string $visitName)
    {
        $this->visitName = $visitName;
    }

    public function setVisitDate(string $visitDate)
    {
        $this->visitDate = $visitDate;
    }

    public function setInvestigatorForm(array $investigatorForm)
    {
        $this->investigatorForm = $investigatorForm;
    }

    public function setRegistrationDate(string $registrationDate)
    {
        $this->registrationDate = $registrationDate;
    }

    public function setMinMaxVisitDate(string $minVisitDate, string $maxVisitDate)
    {
        $this->minVisitDate = $minVisitDate;
        $this->maxVisitDate = $maxVisitDate;
    }

    public function toArray()
    {
        return [
            'studyDetails' => [
                ...$this->seriesReports[0]->getStudyDetails(),
                'Number Of Instances' => array_sum(array_map(function (SeriesReport $series) {
                    return $series->getNumberOfInstances();
                }, $this->seriesReports)),
                'Number Of Series' => sizeof($this->seriesReports)
            ],
            'investigatorForm' => $this->investigatorForm,
            'studyName' => $this->studyName,
            'visitName' => $this->visitName,
            'patientCode' => $this->patientCode,
            'visitDate' => $this->visitDate ?? null,
            'minVisitDate' => $this->minVisitDate ?? null,
            'maxVisitDate' => $this->maxVisitDate ?? null,
            'registrationDate' => $this->registrationDate ?? null
        ];
    }
}
