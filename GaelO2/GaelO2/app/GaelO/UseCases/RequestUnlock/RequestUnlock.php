<?php

namespace App\GaelO\UseCases\RequestUnlock;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;
use Exception;

class RequestUnlock {

    private AuthorizationVisitService $authorizationVisitService;
    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailServices;
    private VisitService $visitService;

    public function __construct( UserRepositoryInterface $userRepositoryInterface, VisitService $visitService, AuthorizationVisitService $authorizationVisitService, MailServices $mailServices)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;

    }

    public function execute(RequestUnlockRequest $requestUnlockRequest, RequestUnlockResponse $requestUnlockResponse){

        try{

            $this->checkAuthorization($requestUnlockRequest->currentUserId,
                            $requestUnlockRequest->role,
                            $requestUnlockRequest->visitId
            );

            $userEntity = $this->userRepositoryInterface->find($requestUnlockRequest->currentUserId);

            $visitContext = $this->visitService->getVisitContext($requestUnlockRequest->visitId);

            $patientCode = $visitContext['patient']['center_code'];
            $visitType = $visitContext['visit_type']['name'];

            if(empty($requestUnlockRequest->message)){
                throw new GaelOBadRequestException('Unlock message should not be empty');
            }

            $this->mailServices->sendUnlockMessage(
                $requestUnlockRequest->currentUserId,
                $requestUnlockRequest->role,
                $userEntity['username'],
                $requestUnlockRequest->studyName,
                $patientCode,
                $requestUnlockRequest->message,
                $visitType
            );

            $requestUnlockResponse->status = 200;
            $requestUnlockResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $requestUnlockResponse->body = $e->getErrorBody();
            $requestUnlockResponse->status = $e->statusCode;
            $requestUnlockResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $role, int $visitId){

        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }
}
