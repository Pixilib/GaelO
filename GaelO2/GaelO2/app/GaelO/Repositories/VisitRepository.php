<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\Models\Visit;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitRepository implements VisitRepositoryInterface {

    public function __construct(){
        $this->visit = new Visit();
        $this->reviewStatus = new ReviewStatus();
    }

    public function find($id) : array {
        return $this->visit->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->visit->findOrFail($id)->delete();
    }

    public function createVisit(string $studyName, int $creatorUserId, int $patientCode, ?string $visitDate, int $visitTypeId,
        string $statusDone, ?string $reasonForNotDone, string $stateInvestigatorForm, string $stateQualityControl){

        $data = [
            'creator_user_id' => $creatorUserId,
            'patient_code' => $patientCode,
            'visit_date' => $visitDate,
            'visit_type_id' => $visitTypeId,
            'status_done' => $statusDone,
            'reason_for_not_done' => $reasonForNotDone,
            'creation_date' => Util::now(),
            'state_investigator_form' => $stateInvestigatorForm,
            'state_quality_control' => $stateQualityControl
        ];

        DB::transaction(function () use ($data, $studyName) {
            $newVisit = $this->visit->create($data);
            $this->reviewStatus->create([
                'visit_id'=>$newVisit->id,
                'study_name'=>$studyName
            ]);
        });
    }

    public function isExistingVisit(int $patientCode, int $visitTypeId) : bool {
        $visit = $this->visit->where([['patient_code', '=', $patientCode], ['visit_type_id', '=', $visitTypeId]])->get();
        return $visit->count() > 0 ? true : false;
    }

    public function updateUploadStatus(int $visitId, string $newUploadStatus) : array {
        $visitEntity = $this->visit->findOrFail($visitId);
        $visitEntity['upload_status'] = $newUploadStatus;
        $visitEntity->save();
        return $visitEntity->toArray();
    }

    public function getVisitContext(int $visitId) : array {

        $dataArray = $this->visit->with(['visitType', 'patient'])->findOrFail($visitId)->toArray();
        return $dataArray;
    }

    public function updateReviewAvailability(int $visitId, string $studyName, bool $available) : void {
        $reviewStatusEntity = $this->visit->findOrFail($visitId)->reviewStatus()->where('study_name', $studyName)->get();
        if($reviewStatusEntity->count() !== 1 ){
            throw new Exception('Should be only one answer');
        }
        $reviewStatusEntity[0]['review_available'] = $available;
        $reviewStatusEntity[0]->save();
    }

    public function getPatientsVisits(int $patientCode) : array {
        //Add withTrashed if bool true
        $visits = $this->visit->with('visitType')->where('patient_code', $patientCode)->get();
        return empty($visits) ? [] : $visits->toArray();
    }

    public function getPatientsVisitsWithReviewStatus(int $patientCode, string $studyName) : array {
        $visits = $this->visit->with('visitType')->where('patient_code', $patientCode)
        ->join('reviews_status', function ($join) {
            $join->on('reviews_status.visit_id', '=', 'visits.id');
        })
        ->where('reviews_status.study_name', $studyName)->get();

        return empty($visits) ? [] : $visits->toArray();
    }

    public function getPatientListVisitsWithContext(array $patientCodeArray) : array {

        $answer = $this->visit->with('visitType')->whereIn('patient_code', $patientCodeArray)->get();
        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getVisitsInStudy(string $studyName) : array {

        $answer = $this->visit->with('visitType')
        ->whereHas('visitType', function($query) use ($studyName) {
            $query->whereHas('visitGroup', function($query) use ($studyName){
                $query->where('study_name', $studyName);
            });
        })
        ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }


    public function hasVisitsInStudy(string $studyName) : bool {

        $visits = $this->getVisitsInStudy($studyName);

        return sizeof($visits) === 0 ? false  : true;

    }

    public function getVisitsInVisitGroup(int $visitGroupId) : array {

        $visits = $this->visit->whereHas('visitType', function($query) use ($visitGroupId) {
            $query->where('visit_group_id', $visitGroupId);
        })
        ->get();
        return $visits->toArray();
    }

    public function hasVisitsInVisitGroup(int $visitGroupId) : bool {
        $visits = $this->getVisitsInVisitGroup($visitGroupId);
        return sizeof($visits) > 0 ? true : false;
    }

    public function getVisitsInStudyAwaitingControllerAction(string $studyName) : array {
        $controllerActionStatusArray = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);

        $answer = $this->visit->with('visitType')
        ->whereHas('visitType', function($query) use ($studyName) {
            $query->whereHas('visitGroup', function($query) use ($studyName){
                $query->where('study_name', $studyName);
            });
        })
        ->where('status_done', Constants::VISIT_STATUS_DONE)
        ->where('upload_status', Constants::UPLOAD_STATUS_DONE)
        ->whereIn('state_quality_control', $controllerActionStatusArray)
        ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }


    public function getVisitsAwaitingReviews(string $studyName) : array{

        $answer = $this->visit->with('visitType')
        ->join('reviews_status', function ($join) use ($studyName) {
            $join->on('visits.id', '=', 'reviews_status.visit_id');
            $join->on('reviews_status.study_name', '=', $studyName);
        })
        ->where('review_available', true)
        ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();

    }

    public function getVisitsAwaitingReviewForUser(string $studyName, int $userId) : array {

        $answer = $this->visit->with('visitType')
        ->join('reviews_status', function ($join) use ($studyName) {
            $join->on('visits.id', '=', 'reviews_status.visit_id');
            $join->on('study_name', '=', $studyName);
        })
        ->where(function($query) use ($studyName, $userId)
            {
                $query->selectRaw('count(*)')
                ->from('reviews')
                ->whereColumn('reviews.visit_id', '=', 'visits.id')
                ->where('study_name', '=', $studyName)
                ->where('validated', true )
                ->where('user_id', $userId);
            }, '=' , 0)
        ->where('review_available', true)
        ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();

    }

    public function getPatientsHavingAtLeastOneAwaitingReviewForUser(string $studyName, int $userId) : array {

        $answer = $this->visit->join('reviews_status', function ($join) use ($studyName) {
            $join->on('visits.id', '=', 'reviews_status.visit_id');
            $join->on('reviews_status.study_name', '=', $studyName);
        })
        ->where(function($query) use ($studyName, $userId)
            {
                $query->selectRaw('count(*)')
                ->from('reviews')
                ->whereColumn('reviews.visit_id', '=', 'visits.id')
                ->where('study_name', '=', $studyName)
                ->where('validated', true )
                ->where('user_id', $userId);
            }, '=' , 0)
        ->where('review_available', true)
        ->distinct('patient_code')
        ->pluck('patient_code');

        return $answer->count() === 0 ? []  : $answer->toArray();

    }

    public function isVisitAvailableForReview(int $visitId, string $studyName, int $userId) : bool{

        $answer = $this->visit->join('reviews_status', function ($join) use ($studyName, $visitId) {
            $join->on('visits.id', '=', $visitId);
            $join->on('reviews_status.study_name', '=', $studyName);
        })
        ->where(function($query) use ($studyName, $userId)
            {
                $query->selectRaw('count(*)')
                ->from('reviews')
                ->whereColumn('reviews.visit_id', '=', 'visits.id')
                ->where('study_name', '=', $studyName)
                ->where('validated', true )
                ->where('user_id', $userId);
            }, '=' , 0)
        ->where('review_available', true )->get();

        return $answer->count() === 0 ? false  : true;
    }

    public function editQc(int $visitId, string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment) : void{
        $visitEntity = $this->visit->findOrFail($visitId);

        $visitEntity['state_quality_control'] = $stateQc;
        $visitEntity['controller_user_id'] = $controllerId;
        $visitEntity['control_date'] = Util::now();
        $visitEntity['image_quality_control'] = $imageQc;
        $visitEntity['form_quality_control'] = $formQc;
        $visitEntity['image_quality_comment'] = $imageQcComment;
        $visitEntity['form_quality_comment'] = $formQcComment;

        $visitEntity->save();
    }

    public function resetQc(int $visitId) : void {

        $visitEntity = $this->visit->findOrFail($visitId);

        $visitEntity['state_quality_control'] = Constants::QUALITY_CONTROL_NOT_DONE;
        $visitEntity['controller_user_id'] = null;
        $visitEntity['control_date'] = null;
        $visitEntity['image_quality_control'] = false;
        $visitEntity['form_quality_control'] = false;
        $visitEntity['image_quality_comment'] = null;
        $visitEntity['form_quality_comment'] = null;
        $visitEntity['corrective_action_user_id'] = null;
        $visitEntity['corrective_action_date'] = null;
        $visitEntity['corrective_action_new_upload'] = false;
        $visitEntity['corrective_action_investigator_form'] = false;
        $visitEntity['corrective_action_comment'] = null;
        $visitEntity['corrective_action_applyed'] = null;

        $visitEntity->save();

    }

    public function setCorrectiveAction(int $visitId, int $investigatorId, bool $newUpload, bool $newInvestigatorForm, bool $correctiveActionApplyed, ?string $comment ) : void{

        $visitEntity = $this->visit->findOrFail($visitId);

        $visitEntity['state_quality_control'] = Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION;
        $visitEntity['corrective_action_user_id'] = $investigatorId;
        $visitEntity['corrective_action_date'] = Util::now();
        $visitEntity['corrective_action_new_upload'] = $newUpload;
        $visitEntity['corrective_action_investigator_form'] = $newInvestigatorForm;
        $visitEntity['corrective_action_comment'] = $comment;
        $visitEntity['corrective_action_applyed'] = $correctiveActionApplyed;

        $visitEntity->save();

    }

    public function updateInvestigatorFormStatus(int $visitId, string $stateInvestigatorFormStatus) : array{
        $visitEntity = $this->visit->findOrFail($visitId);
        $visitEntity['state_investigator_form'] = $stateInvestigatorFormStatus;
        $visitEntity->save();
        return $visitEntity->toArray();
    }

    /**
     * Get visits Imaging awaiting upload (visit done and not uploaded)
     * from centers included in array of user's centers (given in parameters)
     */
    public function getImagingVisitsAwaitingUpload(string $studyName, array $centerCode) : array {

        $answer = $this->visit->with('visitType')
        ->join('patients', function ($join) use ($centerCode) {
            $join->on('visits.patient_code', '=', 'patients.code');
            $join->whereIn('center_code', $centerCode );
        })
        ->whereHas('visitType', function($query) use ($studyName) {
            $query->whereHas('visitGroup', function($query) use ($studyName){
                $query->where('study_name', $studyName);
                $query->whereIn('modality', ['PT', 'MR', 'CT', 'US', 'NM', 'RT']);
            });
        })
        ->where('status_done', Constants::VISIT_STATUS_DONE)
        ->where('upload_status', Constants::UPLOAD_STATUS_NOT_DONE)
        ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }


}
