<?php

namespace App\GaelO\UseCases\SendReminder;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;

class SendReminder
{

    private MailServices $mailService;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(MailServices $mailService, AuthorizationStudyService $authorizationStudyService)
    {
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(SendReminderRequest $sendReminderRequest, SendReminderResponse $sendReminderResponse)
    {

        try {

            $this->checkAuthorization($sendReminderRequest->currentUserId, $sendReminderRequest->studyName);

            $this->checkEmpty($sendReminderRequest->studyName, 'study');
            $this->checkEmpty($sendReminderRequest->userIds, 'userIds');
            $this->checkEmpty($sendReminderRequest->subject, 'subject');
            $this->checkEmpty($sendReminderRequest->content, 'content');

            $senderId = $sendReminderRequest->currentUserId;
            $studyName = $sendReminderRequest->studyName;
            $userIds = $sendReminderRequest->userIds;
            $subject = $sendReminderRequest->subject;
            $content = $sendReminderRequest->content;

            $this->mailService->sendReminder($senderId, $userIds, $studyName, $subject, $content);

            $sendReminderResponse->status = 200;
            $sendReminderResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $sendReminderResponse->body = $e->getErrorBody();
            $sendReminderResponse->status = $e->statusCode;
            $sendReminderResponse->statusText = $e->statusText;
        }

        return $sendReminderResponse;
    }

    private function checkEmpty($inputData, $name)
    {
        if (empty($inputData)) {
            throw new GaelOBadRequestException('Request Missing ' . $name);
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($userId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
