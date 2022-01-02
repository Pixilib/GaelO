<?php

namespace App\GaelO\Repositories;

use App\Models\VisitType;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;

class VisitTypeRepository implements VisitTypeRepositoryInterface {

    public function __construct(VisitType $visitType){
        $this->visitType = $visitType;
    }

    public function find($id) : array {
        return $this->visitType->findOrFail($id)->toArray();
    }

    public function findByName(string $studyName, string $visitGroupName, string $visitTypeName) : array {
        return $this->visitType
        ->where('name', $visitTypeName)
        ->whereHas('visitGroup', function ($query) use ($studyName, $visitGroupName) {
            $query->where('study_name', $studyName);
            $query->where('name', $visitGroupName);
        })
        ->sole()->toArray();
    }

    public function delete($id) : void {
        $this->visitType->findOrFail($id)->delete();
    }

    public function createVisitType(int $visitGroupId, String $name, int $order, bool $localFormNeeded, bool $qcNeeded, bool $reviewNeeded,
                                    bool $optional, int $limitLowDays, int $limitUpDays, String $anonProfile, array $dicomContraints) : void {

        $visitType = new VisitType();

        $visitType->visit_group_id = $visitGroupId;
        $visitType->name = $name;
        $visitType->order = $order;
        $visitType->local_form_needed = $localFormNeeded;
        $visitType->qc_needed = $qcNeeded;
        $visitType->review_needed = $reviewNeeded;
        $visitType->optional = $optional;
        $visitType->limit_low_days = $limitLowDays;
        $visitType->limit_up_days = $limitUpDays;
        $visitType->anon_profile = $anonProfile;
        $visitType->dicom_constraints = $dicomContraints;

        $visitType->save();

    }

    public function hasVisits(int $visitTypeId) : bool {
        $visits = $this->visitType->find($visitTypeId)->visits();
        return $visits->count()>0 ? true : false;
    }

    public function isExistingVisitType(int $visitGroupId, String $name) : bool {
        $visitGroup = $this->visitType->where([['visit_group_id', '=', $visitGroupId], ['name', '=', $name]])->get();
        return sizeof($visitGroup)>0;
    }

    public function getVisitTypesFromIdArray(array $visitTypeIds) : array {
        $visitTypes = $this->visitType->whereIn('id', $visitTypeIds)->get();
        return $visitTypes !== null  ? $visitTypes->toArray() : [];
    }

}
