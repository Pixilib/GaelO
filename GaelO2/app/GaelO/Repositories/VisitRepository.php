<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Constants\Enums\VisitStatusDoneEnum;
use App\GaelO\Exceptions\GaelOException;
use App\Models\Visit;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Util;
use App\Models\ReviewStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $this->visitModel->sharedLock();

            if ($this->visitModel
                ->where('patient_id', $data['patient_id'])
                ->where('visit_type_id', $data['visit_type_id'])
                ->exists()
            ) {
                throw new GaelOException("Visit already existing for this patient");
            };

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

    private function getReviewableVisitTypeIds(string $studyName): array|null
    {
        $studyRule = AbstractGaelOStudy::getSpecificStudyObject($studyName);
        return $studyRule->getReviewableVisitTypeIds();
    }

    private function getReviwablePatientTags(string $studyName): array|null
    {
        $studyRule = AbstractGaelOStudy::getSpecificStudyObject($studyName);
        return $studyRule->getReviewablePatientsTags();
    }

    private function computeMissiveReviewStatusForVisitArray(array $visits, string $studyName): array
    {
        $newVisits = array_map(function ($visit) use ($studyName) {
            return $this->computeMissingReviewStatusForVisit($visit, $studyName);
        }, $visits);
        return $newVisits;
    }

    private function computeMissingReviewStatusForVisit(array $visit, string $studyName): array
    {

        $reviewableVisitTypeIds = $this->getReviewableVisitTypeIds($studyName);
        $reviewablePatientTags = $this->getReviwablePatientTags($studyName);
        //In case of a default value indicating default data has been injected in relationship
        if ($visit['review_status']['review_status'] === null && $visit['review_status']['review_available'] === null) {
            if (
                $reviewablePatientTags != null &&
                sizeof(array_intersect($visit['patient']['metadata']['tags'], $reviewablePatientTags)) === 0
            ) {
                $visit['review_status']['review_status'] = ReviewStatusEnum::NOT_NEEDED->value;
            }
            //Review requirement depends if visit id is expected to be reviewed in ancillary study
            else if (
                $reviewableVisitTypeIds !== null &&
                !in_array($visit['visit_type_id'], $reviewableVisitTypeIds)
            ) {
                $visit['review_status']['review_status'] = ReviewStatusEnum::NOT_NEEDED->value;
            } else {
                $visit['review_status']['review_status'] = ReviewStatusEnum::NOT_DONE->value;
            }

            if ($visit['review_status']['review_status'] === ReviewStatusEnum::NOT_DONE->value) {
                //Review avalability depends on QC status of princeps visit
                if (in_array($visit['state_quality_control'], [QualityControlStateEnum::ACCEPTED->value, QualityControlStateEnum::NOT_NEEDED->value])) {
                    $visit['review_status']['review_available'] = true;
                } else {
                    $visit['review_status']['review_available'] = false;
                }
            } else {
                $visit['review_status']['review_available'] = false;
            }
        }

        return $visit;
    }

    public function getVisitWithContextAndReviewStatus(int $visitId, string $studyName): array
    {

        $builder = $this->visitModel->with(['patient', 'visitType', 'visitType.visitGroup', 'creator']);
        $builder->with(['reviewStatus' => function ($query) use ($studyName) {
            $query->where('study_name', $studyName);
        }]);

        $dataArray = $builder->findOrFail($visitId)->toArray();
        $dataArray = $this->computeMissingReviewStatusForVisit($dataArray, $studyName);
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
        $visits = $builder->with('patient', 'visitType', 'visitType.visitGroup')->where('patient_id', $patientId)
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->get();


        if ($visits->count() === 0) {
            return [];
        } else {
            $visits = $visits->toArray();
            $visits = $this->computeMissiveReviewStatusForVisitArray($visits, $studyName);
            return $visits;
        }
    }

    public function getVisitsFromPatientIdsWithContext(array $patientIdArray): array
    {

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup')->whereIn('patient_id', $patientIdArray)->get();
        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getVisitFromPatientIdsWithContextAndReviewStatus(array $patientIdArray, string $studyName): array
    {

        $answer = $this->visitModel
            ->with('patient', 'visitType', 'visitType.visitGroup')
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->whereIn('patient_id', $patientIdArray)
            ->get();

        if ($answer->count() === 0) {
            return [];
        } else {
            $visits = $answer->toArray();
            $visits = $this->computeMissiveReviewStatusForVisitArray($visits,  $studyName);
            return $visits;
        }
    }

    public function getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus(array $patientIdArray, string $studyName): array
    {

        $answer = $this->visitModel
            ->with('patient', 'visitType', 'visitType.visitGroup')
            ->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }])
            ->where('upload_status', UploadStatusEnum::DONE->value)
            ->whereIn('state_investigator_form', [InvestigatorFormStateEnum::NOT_NEEDED->value, InvestigatorFormStateEnum::DONE->value])
            ->whereIn('state_quality_control', [QualityControlStateEnum::NOT_NEEDED->value, QualityControlStateEnum::ACCEPTED->value])
            ->whereIn('patient_id', $patientIdArray)
            ->get();

        if ($answer->count() === 0) {
            return [];
        } else {
            $visits  = $answer->toArray();
            $visits  = $this->computeMissiveReviewStatusForVisitArray($visits, $studyName);
            return $visits;
        }
    }

    public function getVisitsInStudy(string $originalStudyName, bool $withReviewStatus, bool $withPatientCenter, bool $withTrashed, ?string $ancillaryStudyName): array
    {

        $studyName = ($ancillaryStudyName ?? $originalStudyName);
        $queryBuilder = $this->visitModel->with(['visitType', 'visitType.visitGroup', 'patient'])
            ->whereHas('patient', function ($query) use ($originalStudyName) {
                $query->where('study_name', $originalStudyName);
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
        if ($answer->count() === 0) {
            return [];
        } else {
            $visits = $answer->toArray();
            if ($withReviewStatus) $visits = $this->computeMissiveReviewStatusForVisitArray($visits,  $studyName);
            return $visits;
        }
    }


    public function hasVisitsInStudy(string $studyName): bool
    {

        $visits = $this->getVisitsInStudy($studyName, false, false, true, null);

        return sizeof($visits) === 0 ? false  : true;
    }

    public function getVisitOfPatientByVisitTypeName(string $patientId, string $visitGroupName, string $visitTypeName, bool $withReviewStatus, string $studyName): array
    {
        $visits = $this->visitModel
            ->with('visitType', 'visitType.visitGroup', 'patient')
            ->whereHas('patient', function ($query) use ($patientId) {
                $query->where('id', $patientId);
            })
            ->whereHas('visitType', function ($query) use ($visitTypeName) {
                $query->where('name', $visitTypeName);
            })
            ->whereHas('visitType.visitGroup', function ($query) use ($visitGroupName) {
                $query->where('name', $visitGroupName);
            });

        if ($withReviewStatus) {
            $visits->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }]);
        }

        $visit = $visits->sole()->toArray();
        if ($withReviewStatus) $visit = $this->computeMissingReviewStatusForVisit($visit, $studyName);
        return $visit;
    }

    public function getVisitsInVisitType(int $visitTypeId, bool $withReviewStatus = false, string $studyName = null, bool $withTrashed = false, bool $withCenter = false): array
    {

        $visitQuery = $this->visitModel->whereHas('visitType', function ($query) use ($visitTypeId) {
            $query->where('id', $visitTypeId);
        })->with('patient', 'visitType', 'visitType.visitGroup');

        if ($withReviewStatus) {
            $visitQuery->with(['reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            }, 'patient']);
        }

        if ($withCenter) {
            $visitQuery->with('patient.center');
        }

        if ($withTrashed) {
            $visitQuery->withTrashed();
        }

        $answers = $visitQuery->get();
        if ($answers->count() === 0) {
            return [];
        } else {
            $visits = $answers->toArray();
            if ($withReviewStatus && $studyName) $visits = $this->computeMissiveReviewStatusForVisitArray($visits, $studyName);
            return $visits;
        }
    }

    public function getVisitsInStudyAwaitingControllerAction(string $studyName): array
    {
        $controllerActionStatusArray = array(QualityControlStateEnum::NOT_DONE->value, QualityControlStateEnum::WAIT_DEFINITIVE_CONCLUSION->value);

        $answer = $this->visitModel->with('visitType', 'visitType.visitGroup')
            ->whereHas('patient', function ($query) use ($studyName) {
                $query->where('study_name', $studyName);
            })
            ->where('status_done', VisitStatusDoneEnum::DONE->value)
            ->where('upload_status', UploadStatusEnum::DONE->value)
            ->whereIn('state_investigator_form', [InvestigatorFormStateEnum::NOT_NEEDED->value, InvestigatorFormStateEnum::DONE->value])
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
            ->where('status_done', VisitStatusDoneEnum::DONE->value)
            ->where('upload_status', UploadStatusEnum::DONE->value)
            ->whereIn('state_investigator_form', [InvestigatorFormStateEnum::NOT_NEEDED->value, InvestigatorFormStateEnum::DONE->value])
            ->where('state_quality_control', '!=',  QualityControlStateEnum::NOT_NEEDED->value)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function getPatientsHavingAtLeastOneAwaitingReviewForUser(string $originalStudyName, int $userId, ?string $ancillaryStudyName): array
    {
        $studyName = ($ancillaryStudyName ?? $originalStudyName);
        $collection = $this->visitModel
            ->with(['patient', 'reviewStatus' => function ($query) use ($studyName) {
                $query->where('study_name', ($studyName));
            }])
            ->whereHas('patient', function ($query) use ($originalStudyName) {
                $query->where('study_name', $originalStudyName);
            })
            ->where(function ($query) use ($studyName, $userId) {
                $query->selectRaw('count(*)')
                    ->from('reviews')
                    ->whereColumn('reviews.visit_id', '=', 'visits.id')
                    ->where('study_name', '=', $studyName)
                    ->where('validated', true)
                    ->where('local', false)
                    ->where('user_id', $userId)
                    ->where('deleted_at', null);
            }, '=', 0)->get();

        //Filtered outside the query because confusing laravel to do default value (which is dynamic in our case) + condition after the default value
        $reviewAvailable = $collection->filter(function ($visit, $key) use ($studyName) {
            $visitArray = $visit->toArray();
            $visitArray = $this->computeMissingReviewStatusForVisit($visitArray, $studyName);
            return $visitArray['review_status']['review_available'] === true;
        });

        $patientIds = $reviewAvailable->pluck('patient_id')->unique();

        return $patientIds->count() === 0 ? []  : $patientIds->toArray();
    }

    public function isParentPatientHavingOneVisitAwaitingReview(int $visitId, string $studyName, int $userId): bool
    {
        //Get parent patient
        $patient = $this->visitModel->findOrFail($visitId)->patient()->sole();

        //Select visits available for review in this patient
        $patientVisitAvailableForReview = $this->visitModel
            ->where('patient_id', $patient->id)
            ->with(['patient', 'reviewStatus' => function ($query) use ($studyName) {
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
            ->get();

        $patientVisitAvailableForReview = $patientVisitAvailableForReview->filter(function ($visit, $key) use ($studyName) {
            $visitArray = $visit->toArray();
            $visitArray = $this->computeMissingReviewStatusForVisit($visitArray, $studyName);
            return $visitArray['review_status']['review_available'] === true;
        });

        return $patientVisitAvailableForReview->count() === 0 ? false  : true;
    }

    public function editQc(int $visitId, string $stateQc, int $controllerId, ?bool $imageQc, ?bool $formQc, ?string $imageQcComment, ?string $formQcComment): void
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

        $visitEntity['state_quality_control'] = QualityControlStateEnum::NOT_DONE->value;
        $visitEntity['controller_user_id'] = null;
        $visitEntity['control_date'] = null;
        $visitEntity['image_quality_control'] = null;
        $visitEntity['form_quality_control'] = null;
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

        $visitEntity['state_quality_control'] = QualityControlStateEnum::WAIT_DEFINITIVE_CONCLUSION->value;
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
                    $query->whereIn('modality', ['PT', 'MR', 'CT', 'US', 'NM', 'RTSTRUCT']);
                });
            })
            ->where('status_done', VisitStatusDoneEnum::DONE->value)
            ->where('upload_status', UploadStatusEnum::NOT_DONE->value)
            ->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

    public function reactivateVisit(int $visitId): void
    {
        $this->visitModel->withTrashed()->findOrFail($visitId)->restore();
    }
}
