<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Repositories\VisitRepository;

class VisitService
{
    /**
     * Import patient in study
     */

    public array $successList = [];
    public array $failList = [];

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
    }

    public function updateUploadStatus(int $visitId, string $uploadStatus, string $userEmail){
        $updatedEntity = $this->visitRepository->updateUploadStatus($visitId, $uploadStatus);

        $visitEntity = $this->visitRepository->getVisitContext($visitId);
        $patientCode = $updatedEntity['patient_code'];
        $study = $visitEntity['visit_group']['study_name'];
        $visitType = $visitEntity['visit_type']['name'];
        //If uploaded stats and Investigator form filled or not needed and QC needed and not done,
        //send Notification email to controller to ask them to do QC
        if ($uploadStatus === Constants::UPLOAD_STATUS_DONE
            && $updatedEntity['state_investigator_form'] !== Constants::INVESTIGATOR_FORM_NOT_DONE
        ) {

            if($updatedEntity['state_quality_control'] === Constants::QUALITY_CONTROL_NOT_DONE){
                $this->mailServices->sendUploadedVisitMessage($userEmail, $study, $patientCode, $visitType, true);
            }else if ($updatedEntity['state_quality_control'] == Constants::QUALITY_CONTROL_NOT_NEEDED) {
                $this->mailServices->sendUploadedVisitMessage($userEmail, $study, $patientCode, $visitType, false);
                //SET REVIEW AVAILABLE + INFORM REVIEWER IF AWAITING REVIEW
                dd("la");
            }


        }
    }
}
