<?php

namespace App\GaelO\UseCases\DeleteVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class DeleteVisit{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface,
                                AuthorizationVisitService $authorizationVisitService,
                                TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;

    }

    public function execute(DeleteVisitRequest $deleteVisitRequest,
                            DeleteVisitResponse $deleteVisitResponse){

        try{

            if(empty($deleteVisitRequest->reason)) throw new GaelOBadRequestException('Reason must be specified');

            $visitContext  = $this->visitRepositoryInterface->getVisitContext($deleteVisitRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $visitTypeName = $visitContext['visit_type']['name'];
            $patientCode = $visitContext['patient']['code'];
            $qcStatus = $visitContext['state_quality_control'];
            $visitStatusDone = $visitContext['status_done'];

            $this->checkAuthorization($deleteVisitRequest->currentUserId,
                                        $deleteVisitRequest->role,
                                        $deleteVisitRequest->visitId,
                                        $qcStatus,
                                        $visitStatusDone);

            $this->visitRepositoryInterface->delete($deleteVisitRequest->visitId);

            $actionDetails  = [
                'patient_code' => $patientCode,
                'type_visit' => $visitTypeName,
                'reason' => $deleteVisitRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction($deleteVisitRequest->currentUserId,
                                                $deleteVisitRequest->role,
                                                $studyName,
                                                $deleteVisitRequest->visitId,
                                                Constants::TRACKER_DELETE_VISIT,
                                                $actionDetails);

            $deleteVisitResponse->status = 200;
            $deleteVisitResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $deleteVisitResponse->body = $e->getErrorBody();
            $deleteVisitResponse->status = $e->statusCode;
            $deleteVisitResponse->statusText = $e->statusText;

        } catch (Exception $e){

            throw $e;

        }

    }

    public function checkAuthorization(int $userId, string $role, int $visitId, string $qcStatus, string $statusDone){
        //This role only allowed for Investigator and Supervisor Roles
        if ( ! in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_SUPERVISOR]) ){
            throw new GaelOForbiddenException();
        }

        //If Investigator, only possible to delete Visits with Non finished QC
        if( $role === Constants::ROLE_INVESTIGATOR && in_array($qcStatus, [Constants::QUALITY_CONTROL_REFUSED, Constants::QUALITY_CONTROL_ACCEPTED])){
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }
}
