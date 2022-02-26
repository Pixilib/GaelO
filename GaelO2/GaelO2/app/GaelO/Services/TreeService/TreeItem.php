<?php

namespace App\GaelO\Services\TreeService;

class TreeItem {

    public int $id;
    public string $name;
    public int $order;
    public bool $optional;
    public string $modality;
    public string $modalityName;
    public string $studyName;
    public string $stateInvestigatorForm;
    public string $stateQualityControl;
    public string $uploadStatus;
    public string $statusDone;
    public int $visitTypeId;
    public int $visitGroupId;
    public string $patientId;
    public string $reviewStatus;


    //SK A VOIR SI CETTE ENTITY EST VRAIMENT DIFFERENTE DE L ENTITY VISITE
    public static function createItem(array $visitEntity) : TreeItem {

        $treeItem = new TreeItem();
        $treeItem->id = $visitEntity['id'];
        $treeItem->name = $visitEntity['visit_type']['name'];
        $treeItem->order = $visitEntity['visit_type']['order'];
        $treeItem->optional = $visitEntity['visit_type']['optional'];
        $treeItem->modality = $visitEntity['visit_type']['visit_group']['modality'];
        $treeItem->modalityName = $visitEntity['visit_type']['visit_group']['name'];
        $treeItem->studyName = $visitEntity['visit_type']['visit_group']['study_name'];
        $treeItem->stateInvestigatorForm = $visitEntity['state_investigator_form'];
        $treeItem->stateQualityControl = $visitEntity['state_quality_control'];
        $treeItem->uploadStatus = $visitEntity['upload_status'];
        $treeItem->statusDone = $visitEntity['status_done'];
        $treeItem->visitTypeId = $visitEntity['visit_type']['id'];
        $treeItem->visitGroupId = $visitEntity['visit_type']['visit_group']['id'];
        $treeItem->patientId = $visitEntity['patient_id'];
        $treeItem->reviewStatus = array_key_exists('review_status', $visitEntity) ? $visitEntity['review_status']['review_status'] : null;

        return $treeItem;
    }
}
