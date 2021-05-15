<?php

namespace App\GaelO\Entities;

class StudyEntity {
    public string $name;
    public string $patientCodePrefix;
    public bool $deleted;

    public static function fillFromDBReponseArray(array $array){
        $studyEntity  = new StudyEntity();
        $studyEntity->name = $array['name'];
        $studyEntity->patientCodePrefix = $array['patient_code_prefix'];
        $studyEntity->deleted = $array['deleted_at'] !== null;

        return $studyEntity;
    }
}
