<?php

namespace App\GaelO\Entities;

class StudyEntity {
    public string $name;
    public string $code;
    public bool $deleted;

    public static function fillFromDBReponseArray(array $array){
        $studyEntity  = new StudyEntity();
        $studyEntity->name = $array['name'];
        $studyEntity->code = $array['code'];
        $studyEntity->deleted = $array['deleted_at'] !== null;

        return $studyEntity;
    }
}
