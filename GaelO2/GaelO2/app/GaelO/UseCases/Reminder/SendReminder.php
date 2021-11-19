<?php

namespace App\GaelO\UseCases\Reminder;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;

class SendReminder {

    private MailServices $mailService;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(MailServices $mailService, AuthorizationStudyService $authorizationStudyService)
    {
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
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
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($userId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }

}
