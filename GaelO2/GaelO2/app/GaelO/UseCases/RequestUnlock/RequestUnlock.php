<?php

namespace App\GaelO\UseCases\RequestUnlock;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use Exception;

class RequestUnlock {

    private AuthorizationVisitService $authorizationVisitService;
    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailServices;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct( UserRepositoryInterface $userRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService, MailServices $mailServices)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;

    }

    public function execute(RequestUnlockRequest $requestUnlockRequest, RequestUnlockResponse $requestUnlockResponse){

        try{

            $this->checkAuthorization($requestUnlockRequest->currentUserId,
                            $requestUnlockRequest->role,
                            $requestUnlockRequest->visitId
            );

            $userEntity = $this->userRepositoryInterface->find($requestUnlockRequest->currentUserId);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($requestUnlockRequest->visitId);

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
