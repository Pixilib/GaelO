<?php

namespace App\GaelO\Entities;

class VisitTypeEntity {
    public int $id;
    public int $visitGroupId;
    public string $name;
    public int $order;
    public bool $localFormNeeded;
    public bool $qcProbability;
    public bool $reviewProbability;
    public bool $optional;
    public int $limitLowDays;
    public int $limitUpDays;
    public string $anonProfile;
    public array $dicomConstraints;

    public VisitGroupEntity $visitGroup;

    public static function fillFromDBReponseArray(array $array){
        $visitTypeEntity  = new VisitTypeEntity();
        $visitTypeEntity->id = $array['id'];
        $visitTypeEntity->visitGroupId = $array['visit_group_id'];
        $visitTypeEntity->name = $array['name'];
        $visitTypeEntity->order = $array['order'];
        $visitTypeEntity->localFormNeeded = $array['local_form_needed'];
        $visitTypeEntity->qcProbability = $array['qc_probability'];
        $visitTypeEntity->reviewProbability = $array['review_probability'];
        $visitTypeEntity->optional = $array['optional'];
        $visitTypeEntity->limitLowDays = $array['limit_low_days'];
        $visitTypeEntity->limitUpDays = $array['limit_up_days'];
        $visitTypeEntity->anonProfile = $array['anon_profile'];
        $visitTypeEntity->dicomConstraints = $array['dicom_constraints'];

        return $visitTypeEntity;
    }

    public function setVisitGroupContext(array $visitGroupEntity){
        $this->visitGroup = VisitGroupEntity::fillFromDBReponseArray($visitGroupEntity);
    }

}
