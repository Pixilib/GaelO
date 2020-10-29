<?php

namespace App\GaelO\Repositories;

use App\GaelO\Exceptions\GaelOException;
use App\VisitType;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetVisitType\VisitTypeEntity;
use App\GaelO\Util;
use Illuminate\Support\Facades\Log;

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
        throw new GaelOException('Not updatable, delete / create a new visit type');
    }

    public function find($id){
        return $this->visitType->find($id)->toArray();
    }

    public function delete($id) : void {
        $this->visitType->find($id)->delete();
    }

    public function getAll() : array {
        throw new GaelOException('Non Requestable all Visit Types');
    }

    public function createVisitType(int $visitGroupId, String $name, int $visitOrder, bool $localFormNeeded, bool $qcNeeded, bool $reviewNeeded,
                                    bool $optional, int $limitLowDays, int $limitUpDays, String $anonProfile){

        $data = [
            'visit_group_id'=> $visitGroupId,
            'name' => $name,
            'visit_order'=> $visitOrder,
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

    public function hasVisits(int $visitTypeId){
        $visits = $this->visitType->find($visitTypeId)->visits()->get();
        return $visits->count()>0 ? true : false;
    }

    public function getEntity(int $id) : VisitTypeEntity {
        $entityArray = $this->visitType->find($id)->toArray();
        return VisitTypeEntity::fillFromDBReponseArray($entityArray);
    }

}

?>
