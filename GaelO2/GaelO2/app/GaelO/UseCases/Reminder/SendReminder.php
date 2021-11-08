<?php

namespace App\GaelO\UseCases\Reminder;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\AuthorizationService;

class SendReminder {

    private MailServices $mailService;
    private AuthorizationService $authorizationService;

    public function __construct(MailServices $mailService, AuthorizationService $authorizationService)
    {
        $this->mailService = $mailService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReminderRequest $reminderRequest, ReminderResponse $reminderResponse){

        try{

            $this->checkAuthorization($reminderRequest->currentUserId, $reminderRequest->study);

            $this->checkEmpty($reminderRequest->study, 'study');
            $this->checkEmpty($reminderRequest->role, 'role');
            $this->checkEmpty($reminderRequest->subject, 'subject');
            $this->checkEmpty($reminderRequest->content, 'content');

            if($reminderRequest->role === Constants::ROLE_INVESTIGATOR) {
                //EO checkEmpty() ne passe pas pour centre 0 (renvoie faux)
                isset($reminderRequest->centerCode);
                $this->mailService->sendReminderToInvestigators( get_object_vars ($reminderRequest) );
            } else {
                $this->mailService->sendReminder( get_object_vars($reminderRequest) );    
            }

            $reminderResponse->status = 200;
            $reminderResponse->statusText = 'OK';

        }catch (GaelOException $e) {
            $reminderResponse->body = $e->getErrorBody();
            $reminderResponse->status = $e->statusCode;
            $reminderResponse->statusText = $e->statusText;
        }

        return $reminderResponse;

    }

    private function checkEmpty($inputData, $name){
        if(empty($inputData)){
            throw new GaelOBadRequestException('Request Missing '.$name);
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        };
    }

}
