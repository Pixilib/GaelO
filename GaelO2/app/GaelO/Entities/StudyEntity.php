<?php

namespace App\GaelO\Entities;

class StudyEntity
{

    public string $name;
    public string $code;
    public int $patientCodeLength;
    public string $contactEmail;
    public bool $controllerShowAll;
    public bool $monitorShowAll;
    public bool $documentationMandatory;
    public bool $deleted;
    public bool $creatablePatientsInvestigator;
    public ?string $ancillaryOf;

    //Array of VisitGroupEntities
    public array $visitGroups;

    public static function fillFromDBReponseArray(array $array) : StudyEntity
    {
        $studyEntity  = new StudyEntity();
        $studyEntity->name = $array['name'];
        $studyEntity->code = $array['code'];
        $studyEntity->patientCodeLength = $array['patient_code_length'];
        $studyEntity->contactEmail = $array['contact_email'];
        $studyEntity->controllerShowAll = $array['controller_show_all'];
        $studyEntity->monitorShowAll = $array['monitor_show_all'];
        $studyEntity->ancillaryOf = $array['ancillary_of'];
        $studyEntity->documentationMandatory = $array['documentation_mandatory'];
        $studyEntity->creatablePatientsInvestigator = $array['creatable_patients_investigator'];
        $studyEntity->deleted = $array['deleted_at'] !== null;

        return $studyEntity;
    }

    public function isAncillaryStudy(): bool
    {
        return $this->ancillaryOf == null ? false : true;
    }

    public function isAncillaryStudyOf(String $studyName): bool
    {
        return $this->ancillaryOf === $studyName ? true : false;
    }

    public function getOriginalStudyName(): string
    {
        if ($this->ancillaryOf) return $this->ancillaryOf;
        else return $this->name;
    }

    /**
     * Set Childs visit groupe, $visitGroupEntities is an array of VisitGroupEntity
     */
    public function setVisitGroups(array $visitGroupEntities) : void
    {
        $this->visitGroups = $visitGroupEntities;
    }
}
