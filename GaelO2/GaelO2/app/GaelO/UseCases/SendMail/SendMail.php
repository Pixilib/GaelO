<?php

namespace App\GaelO\UseCases\SendMail;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\MailServices;

class SendMail {

    private MailServices $mailService;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(MailServices $mailService, AuthorizationStudyService $authorizationStudyService)
    {
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(SendMailRequest $sendMailRequest, SendMailResponse $sendMailResponse){

        try{
            $this->checkEmpty($sendMailRequest->study, 'study');
            $this->checkEmpty($sendMailRequest->role, 'role');

            $this->checkAuthorization($sendMailRequest->currentUserId, $sendMailRequest->study, $sendMailRequest->role);

            $this->checkEmpty($sendMailRequest->subject, 'subject');
            $this->checkEmpty($sendMailRequest->content, 'content');

            //EO split 1 use case par role ? 
            if($sendMailRequest->role === Constants::ROLE_SUPERVISOR){
                if(!isset($sendMailRequest->userId)) throw new GaelOBadRequestException('Request Missing recipient');

                //EO Identifier les admins par 0 ? Rajouter un paramètre bool toAdministrators ?
                if($sendMailRequest->userId === 0) $this->mailService->sendMailToAdministrators(
                    $sendMailRequest->study, 
                    $sendMailRequest->subject, 
                    $sendMailRequest->content
                );
                else {
                    //EO checkEmpty() ne passe pas pour id 0 (renvoie faux)
                    $this->mailService->sendMailToUser(
                        $sendMailRequest->userId, 
                        $sendMailRequest->study, 
                        $sendMailRequest->subject, 
                        $sendMailRequest->content
                    );
                }
            } else {
                $this->mailService->sendMailToSupervisors(
                    $sendMailRequest->study, 
                    $sendMailRequest->subject, 
                    $sendMailRequest->content, 
                    $sendMailRequest->patientId, 
                    $sendMailRequest->visitId
                );
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

    private function checkAuthorization(int $userId, string $study, string $role)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($study);
        if (!$this->authorizationStudyService->isAllowedStudy($role)) {
            throw new GaelOForbiddenException();
        };
    }

}
