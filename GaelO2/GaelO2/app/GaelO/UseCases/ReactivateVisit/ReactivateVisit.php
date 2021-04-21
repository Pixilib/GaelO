<?php

namespace App\GaelO\UseCases\ReactivateVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class ReactivateVisit {

    private AuthorizationService $authorizationService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationService $authorizationService,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;

    }

    public function execute(ReactivateVisitRequest $reactivateVisitRequest, ReactivateVisitResponse $reactivateVisitResponse)
    {

        try{

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reactivateVisitRequest->visitId, true);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $modality = $visitContext['visit_type']['visit_group']['modality'];
            $patientCode = $visitContext['patient_code'];


            $this->checkAuthorization($reactivateVisitRequest->currentUserId, $studyName);

            $isExisitingVisit = $this->visitRepositoryInterface->isExistingVisit($patientCode, $visitContext['visit_type']['id']);

            if( $visitContext['deleted_at'] == null ){
                throw new GaelOConflictException("Visit Not Deleted, can't reactivate it");
            }

            if($isExisitingVisit){
                throw new GaelOConflictException("Already existing visit for this Patient / Visit Type, delete it first");
            }

            $this->visitRepositoryInterface->reactivateVisit($reactivateVisitRequest->visitId);

            $actionDetails = [
                'Visit Type' => $visitType,
                'Modality' => $modality,
                'Patient Code' => $patientCode
            ];

            $this->trackerRepositoryInterface->writeAction(
                $reactivateVisitRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $reactivateVisitRequest->visitId,
                Constants::TRACKER_REACTIVATE_VISIT,
                $actionDetails
            );


            $reactivateVisitResponse->status = 200;
            $reactivateVisitResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $reactivateVisitResponse->status = $e->statusCode;
            $reactivateVisitResponse->statusText = $e->statusText;
            $reactivateVisitResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }
}
