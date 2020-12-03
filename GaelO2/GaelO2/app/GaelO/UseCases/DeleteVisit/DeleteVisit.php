<?php

namespace App\GaelO\UseCases\DeleteVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\TrackerService;
use Exception;

class DeleteVisit{

    public function __construct(PersistenceInterface $persistenceInterface,
                                AuthorizationVisitService $authorizationVisitService,
                                TrackerService $trackerService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;

    }

    public function execute(DeleteVisitRequest $deleteVisitRequest,
                            DeleteVisitResponse $deleteVisitResponse){

        try{

            if(empty($deleteVisitRequest->reason)) throw new GaelOBadRequestException('Reason must be specified');

            $visitContext  = $this->persistenceInterface->getVisitContext($deleteVisitRequest->visitId);


            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $visitTypeName = $visitContext['visit_type']['name'];
            $patientCode = $visitContext['patient']['code'];
            $qcStatus = $visitContext['state_quality_control'];

            $this->checkAuthorization($deleteVisitRequest->currentUserId,
                                        $deleteVisitRequest->role,
                                        $deleteVisitRequest->visitId,
                                        $qcStatus);

            $this->persistenceInterface->delete($deleteVisitRequest->visitId);

            $actionDetails  = [
                'patient_code' => $patientCode,
                'type_visit' => $visitTypeName,
                'reason' => $deleteVisitRequest->reason
            ];

            $this->trackerService->writeAction($deleteVisitRequest->currentUserId,
                                                $deleteVisitRequest->role,
                                                $studyName,
                                                $deleteVisitRequest->visitId,
                                                Constants::TRACKER_DELETE_VISIT,
                                                $actionDetails);

            $deleteVisitResponse->status = 200;
            $deleteVisitResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $deleteVisitResponse->status = $e->statusCode;
            $deleteVisitResponse->statusText = $e->statusText;

        } catch (Exception $e){

            throw $e;

        }

    }

    public function checkAuthorization(int $userId, string $role, int $visitId, string $qcStatus){

        //This role only allowed for Investigator and Supervisor Roles
        if ( ! in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_SUPERVISOR]) ){
            throw new GaelOForbiddenException();
        }

        //If Investigator, only possible to delete Visits with Non finished QC
        if( $role === Constants::ROLE_INVESTIGATOR && in_array($qcStatus, [Constants::QUALITY_CONSTROL_REFUSED, Constants::QUALITY_CONTROL_ACCEPTED])){
            throw new GaelOForbiddenException();
        }
        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }
}
