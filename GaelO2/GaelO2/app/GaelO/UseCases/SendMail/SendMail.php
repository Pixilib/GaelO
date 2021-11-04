<?php

namespace App\GaelO\UseCases\SendMail;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\AuthorizationService;

class SendMail {

    private MailServices $mailService;
    private AuthorizationService $authorizationService;

    public function __construct(MailServices $mailService, AuthorizationService $authorizationService)
    {
        $this->mailService = $mailService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(SendMailRequest $sendMailRequest, SendMailResponse $sendMailResponse){

        try{
            $this->checkEmpty($sendMailRequest->study, 'study');
            $this->checkEmpty($sendMailRequest->role, 'role');

            $this->checkAuthorization($sendMailRequest->currentUserId, $sendMailRequest->study, $sendMailRequest->role);

            $this->checkEmpty($sendMailRequest->subject, 'subject');
            $this->checkEmpty($sendMailRequest->content, 'content');

            if($sendMailRequest->role === Constants::ROLE_SUPERVISOR){
                //EO checkEmpty() ne passe pas pour id 0 (renvoie faux)
                isset($sendMailRequest->userId);
                $this->mailService->sendMailToUser( get_object_vars ($sendMailRequest) );
            } else {
                $this->mailService->sendMailToSupervisors( get_object_vars($sendMailRequest) );    
            }

            $sendMailResponse->status = 200;
            $sendMailResponse->statusText = 'OK';

        }catch (GaelOException $e) {
            $sendMailResponse->body = $e->getErrorBody();
            $sendMailResponse->status = $e->statusCode;
            $sendMailResponse->statusText = $e->statusText;
        }

        return $sendMailResponse;

    }

    private function checkEmpty($inputData, $name){
        if(empty($inputData)){
            throw new GaelOBadRequestException('Request Missing '.$name);
        }
    }

    private function checkAuthorization(int $userId, string $studyName, string $role)
    {
        $this->authorizationService->setCurrentUserAndRole($userId, $role);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        };
    }

}
