<?php

namespace App\GaelO\Entities;

class StudyEntity {
    public string $name;
    public string $code;
    public int $patientCodeLength;
    public string $contactEmail;
    public bool $deleted;
    public ?string $ancillaryOf;

    public static function fillFromDBReponseArray(array $array){
        $studyEntity  = new StudyEntity();
        $studyEntity->name = $array['name'];
        $studyEntity->code = $array['code'];
        $studyEntity->patientCodeLength = $array['patient_code_length'];
        $studyEntity->contactEmail = $array['contact_email'];
        $studyEntity->ancillaryOf = $array['ancillary_of'];
        $studyEntity->deleted = $array['deleted_at'] !== null;

        return $studyEntity;
    }
}
