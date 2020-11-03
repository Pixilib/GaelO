<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Repositories\VisitRepository;

class VisitService
{

    public function __construct(VisitRepository $visitRepository, VisitTypeRepository $visitTypeRepository, MailServices $mailServices)
    {
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitRepository = $visitRepository;
        $this->mailServices = $mailServices;
    }

    public function createVisit(
        int $creatorUserId,
        int $patientCode,
        ?string $acquisitionDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone
    ) {

        $visitTypeEntity = $this->visitTypeRepository->getEntity($visitTypeId);

        $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_DONE;
        $stateQualityControl = Constants::QUALITY_CONTROL_NOT_DONE;

        if (!$visitTypeEntity->localFormNeeded) $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        if (!$visitTypeEntity->qcNeeded) $stateQualityControl = Constants::QUALITY_CONTROL_NOT_NEEDED;

        $this->visitRepository->createVisit(
            $creatorUserId,
            $patientCode,
            $acquisitionDate,
            $visitTypeId,
            $statusDone,
            $reasonForNotDone,
            $stateInvestigatorForm,
            $stateQualityControl
        );

        //SK ICI CREER UNE REVIEW STATUS POUR LA VISITE CREE pour faire passer le test creation visite
    }

    public function updateUploadStatus(int $visitId, string $uploadStatus, int $uploaderUserId)
    {

        $updatedEntity = $this->visitRepository->updateUploadStatus($visitId, $uploadStatus);

        $visitEntity = $this->visitRepository->getVisitContext($visitId);
        $patientCode = $updatedEntity['patient_code'];
        $study = $visitEntity['visit_group']['study_name'];
        $visitType = $visitEntity['visit_type']['name'];
        $qcNeeded = $visitEntity['visit_type']['qc_needed'];
        //If uploaded done and investigator done (Done or Not Needed) send notification message
        if (
            $uploadStatus === Constants::UPLOAD_STATUS_DONE
            && $updatedEntity['state_investigator_form'] !== Constants::INVESTIGATOR_FORM_NOT_DONE
        ) {
            $this->mailServices->sendUploadedVisitMessage($uploaderUserId, $study, $patientCode, $visitType, $qcNeeded);
            if(!$qcNeeded) {
                $this->updateReviewAvailability($visitId, true, $study, $patientCode, $visitType);
            }

        }


    }

    /**
     * Update review status of visit
     * if change to available, send notification message to reviewers
     */
    public function updateReviewAvailability(int $visitId, bool $available, string $study, int $patientCode, string $visitType){
        $this->visitRepository->updateReviewAvailability($visitId, $study, $available);
        if($available){
            $this->mailServices->sendAvailableReviewMessage($study, $patientCode, $visitType);
        }

    }
}
