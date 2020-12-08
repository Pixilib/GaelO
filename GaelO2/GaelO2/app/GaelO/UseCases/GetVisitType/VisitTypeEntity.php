<?php

namespace App\GaelO\UseCases\GetVisitType;

class VisitTypeEntity {
    public int $id;
    public int $visitGroupId;
    public String $name;
    public int $visitOrder;
    public bool $localFormNeeded;
    public bool $qcNeeded;
    public bool $reviewNeeded;
    public bool $optional;
    public int $limitLowDays;
    public int $limitUpDays;
    public String $anonProfile;

    public static function fillFromDBReponseArray(array $array){
        $VisitTypeEntity  = new VisitTypeEntity();
        $VisitTypeEntity->id = $array['id'];
        $VisitTypeEntity->visitGroupId = $array['visit_group_id'];
        $VisitTypeEntity->name = $array['name'];
        $VisitTypeEntity->visitOrder = $array['order'];
        $VisitTypeEntity->localFormNeeded = $array['local_form_needed'];
        $VisitTypeEntity->qcNeeded = $array['qc_needed'];
        $VisitTypeEntity->reviewNeeded = $array['review_needed'];
        $VisitTypeEntity->optional = $array['optional'];
        $VisitTypeEntity->limitLowDays = $array['limit_low_days'];
        $VisitTypeEntity->limitUpDays = $array['limit_up_days'];
        $VisitTypeEntity->anonProfile = $array['anon_profile'];

        return $VisitTypeEntity;
    }
}
