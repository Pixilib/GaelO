<?php

namespace App\GaelO\Repositories;

use App\VisitType;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;
use Exception;

class VisitTypeRepository implements PersistenceInterface {

    public function __construct(VisitType $visitType){
        $this->visitType = $visitType;
    }

    public function create(array $data){
        $visitType = new VisitType();
        $model = Util::fillObject($data, $visitType);
        $model->save();
    }

    public function update($id, array $data) : void {
        throw new Exception('Not updatable, delete / create a new visit type');
    }

    public function find($id) : array {
        return $this->visitType->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->visitType->findOrFail($id)->delete();
    }

    public function getAll() : array {
        throw new Exception('Non Requestable all Visit Types');
    }

    public function createVisitType(int $visitGroupId, String $name, int $visitOrder, bool $localFormNeeded, bool $qcNeeded, bool $reviewNeeded,
                                    bool $optional, int $limitLowDays, int $limitUpDays, String $anonProfile){

        $data = [
            'visit_group_id'=> $visitGroupId,
            'name' => $name,
            'order'=> $visitOrder,
            'local_form_needed'=> $localFormNeeded,
            'qc_needed' => $qcNeeded,
            'review_needed'=>$reviewNeeded,
            'optional'=>$optional,
            'limit_low_days'=>$limitLowDays,
            'limit_up_days'=>$limitUpDays,
            'anon_profile'=>$anonProfile
        ];

        $this->create($data);

    }

    public function hasVisits(int $visitTypeId) : bool {
        $visits = $this->visitType->find($visitTypeId)->visits();
        return $visits->count()>0 ? true : false;
    }

    public function isExistingVisitType(int $visitGroupId, String $name) : bool {
        $visitGroup = $this->visitType->where([['visit_group_id', '=', $visitGroupId], ['name', '=', $name]])->get();
        return sizeof($visitGroup)>0;
    }

}

?>
