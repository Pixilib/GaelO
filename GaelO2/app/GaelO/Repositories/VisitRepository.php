<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\Models\Visit;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;
use Illuminate\Support\Facades\DB;

class VisitRepository implements VisitRepositoryInterface
{

    private Visit $visitModel;
    private ReviewStatus $reviewStatusModel;

    public function __construct()
    {
        $this->visitModel = new Visit();
        $this->reviewStatusModel = new ReviewStatus();
    }

    public function delete($id): void
    {
        $this->visitModel->findOrFail($id)->delete();
    }

    public function createVisit(
        string $studyName,
        int $creatorUserId,
        string $patientId,
        ?string $visitDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone,
        string $stateInvestigatorForm,
        string $stateQualityControl,
        string $stateReview
    ): int {

        $data = [
            'creator_user_id' => $creatorUserId,
            'patient_id' => $patientId,
            'visit_date' => $visitDate,
            'visit_type_id' => $visitTypeId,
            'status_done' => $statusDone,
            'reason_for_not_done' => $reasonForNotDone,
            'creation_date' => Util::now(),
            'state_investigator_form' => $stateInvestigatorForm,
            'state_quality_control' => $stateQualityControl
        ];

        $visitId = DB::transaction(function () use ($data, $studyName, $stateReview) {
            $newVisit = $this->visitModel->create($data);
            //create review status to set review status study preset for primary studies
            $this->reviewStatusModel->create([
                'visit_id' => $newVisit->id,
                'study_name' => $studyName,
                'review_status' => $stateReview
            ]);

            return $newVisit->id;
        });

        return $visitId;
    }

    public function isExistingVisit(string $patientId, int $visitTypeId): bool
    {
        $visit = $this->visitModel->where([['patient_id', '=', $patientId], ['visit_type_id', '=', $visitTypeId]])->get();
        return $visit->count() > 0 ? true : false;
    }

    public function updateUploadStatus(int $visitId, string $newUploadStatus): array
    {
        $visitEntity = $this->visitModel->findOrFail($visitId);
        $visitEntity['upload_status'] = $newUploadStatus;
        $visitEntity->save();
        return $visitEntity->toArray();
    }

    public function updateVisitDate(int $visitId, string $visitDate): void
    {
        $visitEntity = $this->visitModel->findOrFail($visitId);
        $visitEntity['visit_date'] = $visitDate;
        $visitEntity->save();
    }

    public function getVisitContext(int $visitId, bool $withTrashed = false): array
    {

        $builder = $this->visitModel->with(['visitType', 'visitType.visitGroup', 'patient']);
        if ($withTrashed) {
            $builder->withTrashed();
        }
        $dataArray = $builder->findOrFail($visitId)->toArray();
        return $dataArray;
    }

    public function getVisitWithContextAndReviewStatus(int $visitId, string $studyName): array
    {

        $builder = $this->visitModel->with(['visitType', 'visitType.visitGroup', 'creator']);
        $builder->with(['reviewStatus' => function ($query) use ($studyName) {
            $query->where('study_name', $studyName);
        }]);

        $dataArray = $builder->findOrFail($visitId)->toArray();
        return $dataArray;

    }

    public function getVisitContextByVisitIdArray(array $visitIdArray): array
    {

        $query = $this->visitModel->with('visitType', 'visitType.visitGroup', 'patient')->withTrashed()->whereIn('id', $visitIdArray);
        $visits = $query->get();

        return empty($visits) ? [] : $visits->toArray();
    }

    public function getPatientsVisits(string $patientId): array
    {
        $visits = $this->visitModel->with('visitType', 'visitType.visitGroup')->where('patient_id', $patientId)->get();
        return empty($visits) ? [] : $visits->toArray();
    }

    public function getAllPatientsVisitsWithReviewStatus(string $patientId, string $studyName, bool $withTrashed): array
    {
        $builder = $this->visitModel;
        if ($withTrashed) {
            $builder = $builder->withTrashed();
        }
        $visits = $builder->with('visitType', 'visitType.visitGroup')->where('patient_id', $patientId)
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->get();

        return empty($visits) ? [] : $visits->toArray();
    }

    public function getVisitsFromPatientIdsWithContext(array $patientIdArray): array
    {

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup')->whereIn('patient_id', $patientIdArray)->get();
        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getVisitFromPatientIdsWithContextAndReviewStatus(array $patientIdArray, string $studyName): array
    {

        $answer = $this->visitModel
            ->with('visitType', 'visitType.visitGroup')
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->whereIn('patient_id', $patientIdArray)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus(array $patientIdArray, string $studyName): array
    {

        $answer = $this->visitModel
            ->with('visitType', 'visitType.visitGroup', 'reviewStatus')
            ->where('upload_status', Constants::UPLOAD_STATUS_DONE)
            ->whereIn('state_investigator_form', [Constants::INVESTIGATOR_FORM_NOT_NEEDED, Constants::INVESTIGATOR_FORM_DONE])
            ->whereIn('state_quality_control', [Constants::QUALITY_CONTROL_NOT_NEEDED, CONSTANTS::QUALITY_CONTROL_ACCEPTED])
            ->whereIn('patient_id', $patientIdArray)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getVisitsInStudy(string $studyName, bool $withReviewStatus, bool $withPatientCenter, bool $withTrashed): array
    {

        $queryBuilder = $this->visitModel->with(['visitType', 'visitType.visitGroup', 'patient'])
            ->whereHas('patient', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            });

        if ($withPatientCenter) {
            $queryBuilder->with('patient.center');
        }
        if ($withReviewStatus) {

            $queryBuilder->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }]);
        }

        if ($withTrashed) {
            $queryBuilder->withTrashed();
        }

        $answer = $queryBuilder->get();
        return $answer->count() === 0 ? []  : $answer->toArray();
    }


    public function hasVisitsInStudy(string $studyName): bool
    {

        $visits = $this->getVisitsInStudy($studyName, false, false, true);

        return sizeof($visits) === 0 ? false  : true;
    }

    public function getVisitsInVisitType(int $visitTypeId, bool $withReviewStatus = false, string $studyName = null, bool $withTrashed = false, bool $withCenter = false): array
    {

        $visits = $this->visitModel->whereHas('visitType', function ($query) use ($visitTypeId) {
            $query->where('id', $visitTypeId);
        })->with('visitType', 'visitType.visitGroup');

        if ($withReviewStatus) {
            $visits->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }, 'patient']);
        }

        if ($withCenter) {
            $visits->with('patient.center');
        }

        if ($withTrashed) {
            $visits->withTrashed();
        }

        return $visits->get()->toArray();
    }

    public function getVisitsInStudyAwaitingControllerAction(string $studyName): array
    {
        $controllerActionStatusArray = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup')
            ->whereHas('patient', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            })
            ->where('status_done', Constants::VISIT_STATUS_DONE)
            ->where('upload_status', Constants::UPLOAD_STATUS_DONE)
            ->whereIn('state_investigator_form', [Constants::INVESTIGATOR_FORM_NOT_NEEDED, Constants::INVESTIGATOR_FORM_DONE])
            ->whereIn('state_quality_control', $controllerActionStatusArray)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getVisitsInStudyNeedingQualityControl(string $studyName): array
    {

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup')
            ->whereHas('patient', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            })
            ->where('status_done', Constants::VISIT_STATUS_DONE)
            ->where('upload_status', Constants::UPLOAD_STATUS_DONE)
            ->whereIn('state_investigator_form', [Constants::INVESTIGATOR_FORM_NOT_NEEDED, Constants::INVESTIGATOR_FORM_DONE])
            ->where('state_quality_control', '!=',  Constants::QUALITY_CONTROL_NOT_NEEDED)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getVisitsAwaitingReviewForUser(string $studyName, int $userId): array
    {

        $visitIdAwaitingReview = $this->reviewStatusModel->where('study_name', $studyName)->where('review_available', true)->select('visit_id')->get()->toArray();

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup')
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->where(function ($query) use ($studyName, $userId) {
                $query->selectRaw('count(*)')
                    ->from('reviews')
                    ->whereColumn('reviews.visit_id', '=', 'visits.id')
                    ->where('study_name', '=', $studyName)
                    ->where('validated', true)
                    ->where('local', false)
                    ->where('user_id', $userId)
                    ->where('deleted_at', null);
            }, '=', 0)
            ->whereIn('id', $visitIdAwaitingReview)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getPatientsHavingAtLeastOneAwaitingReviewForUser(string $studyName, int $userId): array
    {
        $visitIdAwaitingReview = $this->reviewStatusModel->where('study_name', $studyName)->where('review_available', true)->select('visit_id')->get()->toArray();

        $answer = $this->visitModel
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->where(function ($query) use ($studyName, $userId) {
                $query->selectRaw('count(*)')
                    ->from('reviews')
                    ->whereColumn('reviews.visit_id', '=', 'visits.id')
                    ->where('study_name', '=', $studyName)
                    ->where('validated', true)
                    ->where('local', false)
                    ->where('user_id', $userId)
                    ->where('deleted_at', null);
            }, '=', 0)
            ->whereIn('id', $visitIdAwaitingReview)
            ->distinct('patient_id')
            ->pluck('patient_id');

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function isParentPatientHavingOneVisitAwaitingReview(int $visitId, string $studyName, int $userId): bool
    {
        //Get parent patient
        $patient = $this->visitModel->findOrFail($visitId)->patient()->sole();

        //Select visits available for review in this patient
        $patientVisitsIdAvailableForReview = $this->visitModel
            ->where('patient_id', $patient->id)
            ->whereHas('reviewStatus', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
                $query->where('review_available', true);
            })
            ->select('id')->get()->toArray();

        //In these review select visit in which current user didn't validated his review form
        $answer = $this->visitModel
            ->where(function ($query) use ($studyName, $userId) {
                $query->selectRaw('count(*)')
                    ->from('reviews')
                    ->whereColumn('reviews.visit_id', '=', 'visits.id')
                    ->where('study_name', '=', $studyName)
                    ->where('validated', true)
                    ->where('local', false)
                    ->where('user_id', $userId)
                    ->where('deleted_at', null);
            }, '=', 0)
            ->whereIn('id', $patientVisitsIdAvailableForReview)->get();

        return $answer->count() === 0 ? false  : true;
    }

    public function editQc(int $visitId, string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment): void
    {
        $visitEntity = $this->visitModel->findOrFail($visitId);

        $visitEntity['state_quality_control'] = $stateQc;
        $visitEntity['controller_user_id'] = $controllerId;
        $visitEntity['control_date'] = Util::now();
        $visitEntity['image_quality_control'] = $imageQc;
        $visitEntity['form_quality_control'] = $formQc;
        $visitEntity['image_quality_comment'] = $imageQcComment;
        $visitEntity['form_quality_comment'] = $formQcComment;

        $visitEntity->save();
    }

    public function resetQc(int $visitId): void
    {

        $visitEntity = $this->visitModel->findOrFail($visitId);

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
        $visitEntity['corrective_action_applied'] = null;

        $visitEntity->save();
    }

    public function setCorrectiveAction(int $visitId, int $investigatorId, bool $newUpload, bool $newInvestigatorForm, bool $correctiveActionApplied, ?string $comment): void
    {

        $visitEntity = $this->visitModel->findOrFail($visitId);

        $visitEntity['state_quality_control'] = Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION;
        $visitEntity['corrective_action_user_id'] = $investigatorId;
        $visitEntity['corrective_action_date'] = Util::now();
        $visitEntity['corrective_action_new_upload'] = $newUpload;
        $visitEntity['corrective_action_investigator_form'] = $newInvestigatorForm;
        $visitEntity['corrective_action_comment'] = $comment;
        $visitEntity['corrective_action_applied'] = $correctiveActionApplied;

        $visitEntity->save();
    }

    public function updateInvestigatorFormStatus(int $visitId, string $stateInvestigatorFormStatus): array
    {
        $visitEntity = $this->visitModel->findOrFail($visitId);
        $visitEntity['state_investigator_form'] = $stateInvestigatorFormStatus;
        $visitEntity->save();
        return $visitEntity->toArray();
    }

    /**
     * Get visits Imaging awaiting upload (visit done and not uploaded)
     * from centers included in array of user's centers (given in parameters)
     */
    public function getImagingVisitsAwaitingUpload(string $studyName, array $centerCode): array
    {

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup', 'patient')
            ->whereHas('patient', function ($query) use ($centerCode) {
                $query->whereIn('center_code', $centerCode);
            })
            ->whereHas('visitType', function ($query) use ($studyName) {
                $query->whereHas('visitGroup', function ($query) use ($studyName) {
                    $query->where('study_name', $studyName);
                    $query->whereIn('modality', ['PT', 'MR', 'CT', 'US', 'NM', 'RT']);
                });
            })
            ->where('status_done', Constants::VISIT_STATUS_DONE)
            ->where('upload_status', Constants::UPLOAD_STATUS_NOT_DONE)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function reactivateVisit(int $visitId): void
    {
        $this->visitModel->withTrashed()->findOrFail($visitId)->restore();
    }
}
