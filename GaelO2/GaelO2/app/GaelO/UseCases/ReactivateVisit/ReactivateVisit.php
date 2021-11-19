<?php

namespace App\GaelO\UseCases\ReactivateVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class ReactivateVisit {

    private AuthorizationUserService $authorizationUserService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
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
            $patientId = $visitContext['patient_id'];


            $this->checkAuthorization($reactivateVisitRequest->currentUserId, $studyName);

            $isExisitingVisit = $this->visitRepositoryInterface->isExistingVisit($patientId, $visitContext['visit_type']['id']);

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
                'Patient ID' => $patientId
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
        $this->authorizationUserService->setUserId($currentUserId);
        if ( ! $this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)){
            throw new GaelOForbiddenException();
        }

    }
}
