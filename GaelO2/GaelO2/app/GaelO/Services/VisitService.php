<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\ReviewStatusRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Entities\VisitTypeEntity;

class VisitService
{
    private VisitRepository $visitRepository;
    private ReviewRepository $reviewRepository;
    private VisitTypeRepository $visitTypeRepository;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private MailServices $mailServices;
    private ReviewStatusRepository $reviewStatusRepository;

    private int $visitId;

    public function __construct(
        VisitRepository $visitRepository,
        ReviewRepository $reviewRepository,
        ReviewStatusRepository $reviewStatusRepository,
        VisitTypeRepository $visitTypeRepository,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        MailServices $mailServices
    ) {
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitRepository = $visitRepository;
        $this->mailServices = $mailServices;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->reviewStatusRepository = $reviewStatusRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function setVisitId(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function getVisitContext(): array
    {
        return $this->visitRepository->getVisitContext($this->visitId);
    }

    public function getVisitSeriesIdsDicomArray(bool $deleted) : array
    {
        $studyInstanceUid = $this->dicomStudyRepositoryInterface->getStudyInstanceUidFromVisit($this->visitId);
        $seriesEntities = $this->dicomStudyRepositoryInterface->getChildSeries($studyInstanceUid, $deleted);
        $seriesOrthancIdArray = array_map(function ($series) {
            return $series['orthanc_id'];
        }, $seriesEntities);

        return $seriesOrthancIdArray;
    }

    public function createVisit(
        string $studyName,
        int $creatorUserId,
        string $patientId,
        ?string $visitDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone
    ) : int {

        $visitTypeData = $this->visitTypeRepository->find($visitTypeId);
        $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitTypeData);

        $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_DONE;
        $stateQualityControl = Constants::QUALITY_CONTROL_NOT_DONE;

        if (!$visitTypeEntity->localFormNeeded) $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        if (!$visitTypeEntity->qcNeeded) $stateQualityControl = Constants::QUALITY_CONTROL_NOT_NEEDED;

        $visitId = $this->visitRepository->createVisit(
            $studyName,
            $creatorUserId,
            $patientId,
            $visitDate,
            $visitTypeId,
            $statusDone,
            $reasonForNotDone,
            $stateInvestigatorForm,
            $stateQualityControl
        );

        return $visitId;
    }

    public function updateUploadStatus(string $uploadStatus)
    {

        if($uploadStatus === Constants::UPLOAD_STATUS_NOT_DONE){
            $visitContext = $this->visitRepository->getVisitContext($this->visitId);
            if($visitContext['state_investigator_form'] === Constants::INVESTIGATOR_FORM_DONE) {
                $this->reviewRepository->unlockInvestigatorForm($this->visitId);
                $this->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DRAFT);
            }

        }

        $updatedEntity = $this->visitRepository->updateUploadStatus($this->visitId, $uploadStatus);

        if (
            $updatedEntity['upload_status'] === Constants::UPLOAD_STATUS_DONE
            && ($updatedEntity['state_investigator_form'] === Constants::INVESTIGATOR_FORM_NOT_NEEDED || $updatedEntity['state_investigator_form'] === Constants::INVESTIGATOR_FORM_DONE)
        ) {
            $this->sendUploadEmailAndSkipQcIfNeeded($this->visitId);
        }
    }

    public function updateInvestigatorFormStatus(string $stateInvestigatorForm)
    {
        $updatedEntity = $this->visitRepository->updateInvestigatorFormStatus($this->visitId, $stateInvestigatorForm);
        if (
            $updatedEntity['upload_status'] === Constants::UPLOAD_STATUS_DONE
            && $updatedEntity['state_investigator_form'] !== Constants::INVESTIGATOR_FORM_NOT_DONE
        ) {
            $this->sendUploadEmailAndSkipQcIfNeeded($this->visitId);
        }
    }

    private function sendUploadEmailAndSkipQcIfNeeded()
    {
        //If uploaded done and investigator done (Done or Not Needed) send notification message
        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);
        $patientId = $visitEntity['patient_id'];
        $study = $visitEntity['patient']['study_name'];
        $visitType = $visitEntity['visit_type']['name'];
        $qcNeeded = $visitEntity['visit_type']['qc_needed'];

        $this->mailServices->sendUploadedVisitMessage($this->visitId, $visitEntity['creator_user_id'], $study, $patientId, $visitType, $qcNeeded);
        //If Qc NotNeeded mark visit as available for review
        if (!$qcNeeded) {
            $this->updateReviewAvailability(true, $study, $patientId, $visitType);
        }
    }

    /**
     * Update review status of visit
     * if change to available, send notification message to reviewers
     */
    public function updateReviewAvailability(bool $available, string $study, string $patientId, string $visitType)
    {
        $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $study, $available);
        if ($available) {
            $this->mailServices->sendAvailableReviewMessage($this->visitId, $study, $patientId, $visitType);
        }
    }


    public function editQc(string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment)
    {

        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);
        $localFormNeeded = $visitEntity['visit_type']['local_form_needed'];
        $reviewNeeded = $visitEntity['visit_type']['review_needed'];
        $studyName = $visitEntity['patient']['study_name'];

        $this->visitRepository->editQc($this->visitId, $stateQc, $controllerId, $imageQc, $formQc, $imageQcComment, $formQcComment);

        if ($stateQc === Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED && $localFormNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewRepository->unlockInvestigatorForm($this->visitId);
            $this->visitRepository->updateInvestigatorFormStatus($this->visitId, Constants::INVESTIGATOR_FORM_DRAFT);
        }

        if ($stateQc === Constants::QUALITY_CONTROL_ACCEPTED && $reviewNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName , true);
        }
    }

    public function resetQc(): void
    {
        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $this->visitRepository->resetQc($this->visitId);
        $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName , false);
    }

    public function getReviewStatus(string $studyName)
    {
        return $this->reviewStatusRepository->getReviewStatus($this->visitId, $studyName);
    }


    //SK ICI PROBABILITE EN BASE DE DONNE ET DOIT POUVOIR ETRE OVERRIDE PAR LE CUSTOM OBJECT
    /*
    public function isReviewNeeded(array $visitTypeEntity) {


    }

    public function isQcNeeded(array $visitTypeEntity) {

    }
    */
}
