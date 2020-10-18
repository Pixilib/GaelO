<?php

namespace App\GaelO\UseCases\GetVisitGroup;

class VisitGroupEntity {
    public int $id;
    public String $studyName;
    public String $modality;

    public static function fillFromDBReponseArray(array $array){
        $visitGroupEntity  = new VisitGroupEntity();
        $visitGroupEntity->id = $array['id'];
        $visitGroupEntity->studyName = $array['study_name'];
        $visitGroupEntity->modality = $array['modality'];

        return $visitGroupEntity;
    }
}
