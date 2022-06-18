<?php

namespace App\GaelO\Entities;

class VisitGroupEntity
{
    public int $id;
    public String $studyName;
    public String $modality;
    public String $name;

    public array $visitTypes;

    public static function fillFromDBReponseArray(array $array) : VisitGroupEntity
    {
        $visitGroupEntity  = new VisitGroupEntity();
        $visitGroupEntity->id = $array['id'];
        $visitGroupEntity->studyName = $array['study_name'];
        $visitGroupEntity->name = $array['name'];
        $visitGroupEntity->modality = $array['modality'];

        return $visitGroupEntity;
    }

    /**
     * Set child VisitTypes, $visitTypeEntities is an array of VisitTypeEntity
     */
    public function setVisitTypes(array $visitTypeEntities) : void
    {
        $this->visitTypes = $visitTypeEntities;
    }
}
