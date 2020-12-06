<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\OrthancStudyRepository;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\ReviewStatusRepository;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Repositories\VisitRepository;

class VisitService
{

    private PatientRepository $patientRepository;
    private StudyRepository $studyRepository;
    private VisitRepository $visitRepository;
    private ReviewRepository $reviewRepository;
    private VisitTypeRepository $visitTypeRepository;
    private OrthancStudyRepository $orthancStudyRepository;
    private MailServices $mailServices;
    private ReviewStatusRepository $reviewStatusRepository;

    public function __construct(
                            PatientRepository $patientRepository,
                            StudyRepository $studyRepository,
                            VisitRepository $visitRepository,
                            ReviewRepository $reviewRepository,
                            ReviewStatusRepository $reviewStatusRepository,
                            VisitTypeRepository $visitTypeRepository,
                            OrthancStudyRepository $orthancStudyRepository,
                            MailServices $mailServices)
    {
        $this->patientRepository = $patientRepository;
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitRepository = $visitRepository;
        $this->mailServices = $mailServices;
        $this->orthancStudyRepository = $orthancStudyRepository;
        $this->studyRepository = $studyRepository;
        $this->reviewStatusRepository = $reviewStatusRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function getVisitContext(int $visitId) : array {
        return $this->visitRepository->getVisitContext($visitId);
    }

    public function getVisitData(int $visitId) : array {
        return $this->visitRepository->find($visitId);
    }

    public function getVisitSeriesIdsDicomArray(int $visitId, bool $deleted){
        $studyOrthancId = $this->orthancStudyRepository->getStudyOrthancIDFromVisit($visitId);
        $seriesEntities = $this->orthancStudyRepository->getChildSeries($studyOrthancId, $deleted);
        $seriesOrthancIdArray = array_map(function($series){
            return $series['orthanc_id'];
        }, $seriesEntities);
        return $seriesOrthancIdArray;

    }

    public function createVisit(
        string $studyName,
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
            $studyName,
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

    public function updateUploadStatus(int $visitId, string $uploadStatus, int $uploaderUserId)
    {

        $updatedEntity = $this->visitRepository->updateUploadStatus($visitId, $uploadStatus);

        //If uploaded done and investigator done (Done or Not Needed) send notification message
        if (
            $uploadStatus === Constants::UPLOAD_STATUS_DONE
            && $updatedEntity['state_investigator_form'] !== Constants::INVESTIGATOR_FORM_NOT_DONE
        ) {
            $visitEntity = $this->getVisitContext($visitId);
            $patientCode = $updatedEntity['patient_code'];
            $study = $visitEntity['visit_type']['visit_group']['study_name'];
            $visitType = $visitEntity['visit_type']['name'];
            $qcNeeded = $visitEntity['visit_type']['qc_needed'];

            $this->mailServices->sendUploadedVisitMessage($uploaderUserId, $study, $patientCode, $visitType, $qcNeeded);
            //If Qc NotNeeded mark visit as available for review
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

    public function getAvailableVisitToCreate(string $patientCode) : array {

        $patientEntity = $this->patientRepository->find($patientCode);

        //If Patient status different from Included, No further visit creation is possible
        if($patientEntity['inclusion_status'] !== Constants::PATIENT_INCLUSION_STATUS_INCLUDED){
            return [];
        }

        //Get Created Patient's Visits
        $createdVisitsArray = $this->patientRepository->getPatientsVisits($patientCode);

        $createdVisitMap = [];

        //Build array of Created visit Order indexed by visit group modality
        foreach($createdVisitsArray as $createdVisit){
            $visitOrder = $createdVisit['visit_type']['visit_order'];
            $modality = $createdVisit['visit_type']['visit_group']['modality'];
            $createdVisitMap[$modality][]=$visitOrder;
        }


        //Get Possibles visits groups and type from study
        $studyVisitsDetails = $this->studyRepository->getStudyDetails($patientEntity['study_name']);

        $studyVisitMap = [];
        //Reindex possibiles visits by modality and order
        foreach( $studyVisitsDetails['visit_group_details'] as $visitGroupDetails){

            foreach($visitGroupDetails['visit_types'] as $visitType){

                $studyVisitMap[ $visitGroupDetails['modality'] ] [$visitType['visit_order'] ] = [
                    'groupId' => $visitType['visit_group_id'],
                    'typeId'=>$visitType['id'],
                    'name' => $visitType['name']
                ];
            }

        }

        $visitToCreateMap = [];

        //Search for visits that have not been created
        foreach( $studyVisitMap as $modality => $visitsArray){

            foreach($visitsArray as $visitOrder => $visit){
                if(  ! isset($createdVisitMap[$modality]) || !in_array($visitOrder, $createdVisitMap[$modality]) ){
                    $visitToCreateMap[$modality][$visitOrder] = $visit;
                }
            }

        }

        return $visitToCreateMap;

    }

    public function editQc(int $visitId, string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment){

        $visitEntity = $this->getVisitContext($visitId);
        $localFormNeeded = $visitEntity['visit_type']['local_form_needed'];

        $this->visitRepository->editQc($visitId, $stateQc, $controllerId, $imageQc, $formQc, $imageQcComment, $formQcComment);

        if($stateQc === Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED && $localFormNeeded){
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewRepository->unlockInvestigatorForm($visitId);
            $this->visitRepository->updateInvestigatorForm($visitId, Constants::INVESTIGATOR_FORM_DRAFT);
        }
    }

    public function resetQc(int $visitId) : void {
        $this->visitRepository->resetQc($visitId);
    }

    public function getReviewStatus(int $visitId, string $studyName){
        return $this->reviewStatusRepository->getReviewStatus($visitId, $studyName);
    }
}
