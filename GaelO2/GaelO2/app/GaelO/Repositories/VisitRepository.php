<?php

namespace App\GaelO\Repositories;

use App\Visit;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class VisitRepository implements PersistenceInterface {

    public function __construct(){
        $this->visit = new Visit();
    }

    public function create(array $data){
        $visit = new Visit();
        $model = Util::fillObject($data, $visit);
        $model->save();
    }

    public function update($id, array $data) : void {
        $model = $this->visit->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->visit->find($id)->toArray();
    }

    public function delete($id) : void {
        $this->visit->find($id)->delete();
    }

    public function createVisit(int $creatorUserId, int $patientCode, ?string $acquisitionDate, int $visitTypeId,
        string $statusDone, ?string $reasonForNotDone, string $stateInvestigatorForm, string $stateQualityControl){

        $data = [
            'creator_user_id' => $creatorUserId,
            'patient_code' => $patientCode,
            'acquisition_date' => $acquisitionDate,
            'visit_type_id' => $visitTypeId,
            'status_done' => $statusDone,
            'reason_for_not_done' => $reasonForNotDone,
            'creation_date' => Util::now(),
            'state_investigator_form' => $stateInvestigatorForm,
            'state_quality_control' => $stateQualityControl
        ];
        $this->create($data);
    }

    public function getAll() : array {
        $visits = $this->visit->get();
        return empty($visits) ? []  : $visits->toArray();
    }

    public function getReviewsInStudy(string $studyName){
        $reviews = $this->visit->reviews()->where([['study_name', '=', $studyName]])->get();
        return empty($reviews) ? []  : $reviews->toArray();
    }

    public function getReviewStatusInStudy(string $studyName){
        return $this->visit->reviewStatus()->where([['study_name', '=', $studyName]])->firstOrFail()->toArray();
    }

    public function isExistingVisit(int $patientCode, int $visitTypeId) : bool {
        $visit = $this->visit->where([['patient_code', '=', $patientCode], ['visit_type_id', '=', $visitTypeId]])->get();
        return $visit->count() > 0 ? true : false;
    }

    public function updateUploadStatus(int $visitId, string $newUploadStatus) : array {
        $visitEntity = $this->visit->find($visitId);
        $visitEntity['upload_status'] = $newUploadStatus;
        $visitEntity->save();
        return $visitEntity->toArray();
    }

    public function getVisitContext(int $visitId) : array {
        $dataArray = $this->visit->find($visitId)->with('visitType')->first()->toArray();
        $dataArray['visit_group']  = $this->visit->find($visitId)->visitType->visitGroup->toArray();
        return $dataArray;
    }

    public function updateReviewAvailability(int $visitId, string $studyName, bool $available) : void {
        $reviewStatusEntity = $this->visit->find($visitId)->reviewStatus()->where('study_name', $studyName)->firstOrFail();
        $reviewStatusEntity['review_available'] = $available;
        $reviewStatusEntity->save();
    }


}

?>
