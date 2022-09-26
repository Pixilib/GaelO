<?php

namespace App\GaelO\Services\StoreObjects;

class OrthancStudyImport
{
    private string $studyOrthancId;
    private int $numberOfInstances;

    public function __construct(string $studyOrthancId, int $numberOfInstances)
    {
        $this->studyOrthancId = $studyOrthancId;
        $this->numberOfInstances = $numberOfInstances;
    }

    public function getStudyOrthancId(): string
    {
        return $this->studyOrthancId;
    }

    public function getNumberOfInstances(): int
    {
        return $this->numberOfInstances;
    }
}
