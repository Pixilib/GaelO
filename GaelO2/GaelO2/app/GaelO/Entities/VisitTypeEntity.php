<?php

namespace App\GaelO\Entities;

class VisitTypeEntity {
    public int $id;
    public int $visitGroupId;
    public string $name;
    public int $visitOrder;
    public bool $localFormNeeded;
    public bool $qcNeeded;
    public bool $reviewNeeded;
    public bool $optional;
    public int $limitLowDays;
    public int $limitUpDays;
    public string $anonProfile;
    public array $dicomConstraints;

    public static function fillFromDBReponseArray(array $array){
        $visitTypeEntity  = new VisitTypeEntity();
        $visitTypeEntity->id = $array['id'];
        $visitTypeEntity->visitGroupId = $array['visit_group_id'];
        $visitTypeEntity->name = $array['name'];
        $visitTypeEntity->visitOrder = $array['order'];
        $visitTypeEntity->localFormNeeded = $array['local_form_needed'];
        $visitTypeEntity->qcNeeded = $array['qc_needed'];
        $visitTypeEntity->reviewNeeded = $array['review_needed'];
        $visitTypeEntity->optional = $array['optional'];
        $visitTypeEntity->limitLowDays = $array['limit_low_days'];
        $visitTypeEntity->limitUpDays = $array['limit_up_days'];
        $visitTypeEntity->anonProfile = $array['anon_profile'];
        $visitTypeEntity->dicomConstraints = $array['dicom_constraints'];

        return $visitTypeEntity;
    }
}
