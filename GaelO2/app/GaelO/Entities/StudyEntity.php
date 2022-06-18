<?php

namespace App\GaelO\Entities;

class StudyEntity {

    public string $name;
    public string $code;
    public int $patientCodeLength;
    public string $contactEmail;
    public bool $controllerShowAll;
    public bool $monitorShowAll;
    public bool $deleted;
    public ?string $ancillaryOf;

    //Array of VisitGroupEntities
    public array $visitGroups;

    public static function fillFromDBReponseArray(array $array){
        $studyEntity  = new StudyEntity();
        $studyEntity->name = $array['name'];
        $studyEntity->code = $array['code'];
        $studyEntity->patientCodeLength = $array['patient_code_length'];
        $studyEntity->contactEmail = $array['contact_email'];
        $studyEntity->controllerShowAll = $array['controller_show_all'];
        $studyEntity->monitorShowAll = $array['monitor_show_all'];
        $studyEntity->ancillaryOf = $array['ancillary_of'];
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

    public function getOriginalStudyName() : string {
        if ($this->ancillaryOf) return $this->ancillaryOf;
        else return $this->name;
    }

    public function setVisitGroups(array $visitGroupEntities){
        $this->visitGroups = $visitGroupEntities;
    }
}
