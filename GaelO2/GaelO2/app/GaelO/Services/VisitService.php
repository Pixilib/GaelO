<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
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
    private MailServices $mailServices;
    private ReviewStatusRepository $reviewStatusRepository;

    private int $visitId;

    public function __construct(
        VisitRepository $visitRepository,
        ReviewRepository $reviewRepository,
        ReviewStatusRepository $reviewStatusRepository,
        VisitTypeRepository $visitTypeRepository,
        MailServices $mailServices
    ) {
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitRepository = $visitRepository;
        $this->mailServices = $mailServices;
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
        $stateReview = Constants::REVIEW_STATUS_NOT_DONE;

        //SK ICI PASSER EN CALCUL DE PROBABILITE ET ISOLER CE SERVICE DANS UNE AUTRE CLASSE?
        if (!$visitTypeEntity->localFormNeeded) $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        if (!$visitTypeEntity->qcNeeded) $stateQualityControl = Constants::QUALITY_CONTROL_NOT_NEEDED;
        if (!$visitTypeEntity->reviewNeeded) $stateReview = Constants::REVIEW_STATUS_NOT_NEEDED;

        $visitId = $this->visitRepository->createVisit(
            $studyName,
            $creatorUserId,
            $patientId,
            $visitDate,
            $visitTypeId,
            $statusDone,
            $reasonForNotDone,
            $stateInvestigatorForm,
            $stateQualityControl,
            $stateReview
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
        $visitType = $visitEntity['visit_type']['name'];
        $studyName = $visitEntity['patient']['study_name'];

        $reviewStatus = $this->getReviewStatus($studyName);

        $qcNeeded = $visitEntity['state_quality_control'] !== Constants::QUALITY_CONTROL_NOT_NEEDED;
        $reviewNeeded = $reviewStatus['review_status'] !== Constants::REVIEW_STATUS_NOT_NEEDED;

        $this->mailServices->sendUploadedVisitMessage($this->visitId, $visitEntity['creator_user_id'], $studyName, $patientId, $visitType, $qcNeeded);
        //If Qc NotNeeded mark visit as available for review
        if (!$qcNeeded && $reviewNeeded ) {
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName, true);
            $this->mailServices->sendAvailableReviewMessage($this->visitId, $studyName, $patientId, $visitType);
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
