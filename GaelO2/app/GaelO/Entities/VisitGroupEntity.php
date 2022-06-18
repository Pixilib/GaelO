<?php

namespace App\GaelO\Entities;

class VisitGroupEntity {
    public int $id;
    public String $studyName;
    public String $modality;
    public String $name;

    public array $visitTypes;

    public static function fillFromDBReponseArray(array $array){
        $visitGroupEntity  = new VisitGroupEntity();
        $visitGroupEntity->id = $array['id'];
        $visitGroupEntity->studyName = $array['study_name'];
        $visitGroupEntity->name = $array['name'];
        $visitGroupEntity->modality = $array['modality'];

        return $visitGroupEntity;
    }

    public function setVisitTypes(array $visitTypeEntities){
        $this->visitTypes = $visitTypeEntities;
    }
}
