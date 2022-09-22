<?php

namespace App\GaelO\UseCases\SendMail;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Services\MailServices;

class SendMail
{
    private MailServices $mailService;
    private AuthorizationStudyService $authorizationStudyService;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(
        MailServices $mailService,
        AuthorizationStudyService $authorizationStudyService,
        AuthorizationUserService $authorizationUserService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->authorizationUserService = $authorizationUserService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(SendMailRequest $sendMailRequest, SendMailResponse $sendMailResponse)
    {

        try {

            $currentUserId = $sendMailRequest->currentUserId;
            $studyName = $sendMailRequest->studyName;
            $role = $sendMailRequest->role;
            $subject = $sendMailRequest->subject;
            $content = $sendMailRequest->content;
            $userIds = $sendMailRequest->userIds;

            $this->checkEmpty($role, 'role');
            $this->checkEmpty($subject, 'subject');
            $this->checkEmpty($content, 'content');

            if ($role !== Constants::ROLE_ADMINISTRATOR) $this->checkAuthorization($currentUserId, $studyName, $role);
            else $this->checkAuthorizationAdmin($currentUserId);

            if ($role === Constants::ROLE_SUPERVISOR && $sendMailRequest->toAdministrators) {
                $this->mailService->sendMailToAdministrators(
                    $currentUserId,
                    $studyName,
                    $subject,
                    $content,
                );
            } else if ($role === Constants::ROLE_SUPERVISOR || $role === Constants::ROLE_ADMINISTRATOR) {
                $this->checkEmpty($userIds, 'recipient');
                $this->mailService->sendMailToUser(
                    $currentUserId,
                    $userIds,
                    $studyName,
                    $subject,
                    $content
                );
            } else if ($role === Constants::ROLE_INVESTIGATOR || $role === Constants::ROLE_CONTROLLER || $role === Constants::ROLE_REVIEWER || $role === Constants::ROLE_MONITOR) {
                if (isset($userIds)) throw new GaelOForbiddenException();
                $this->mailService->sendMailToSupervisors(
                    $currentUserId,
                    $studyName,
                    $subject,
                    $content,
                    $sendMailRequest->patientId,
                    $sendMailRequest->visitId
                );
            }

            $actionsDetails = [
                'subject' => $subject,
                'content' => $content,
                'to_user_ids' => $userIds,
                'to_administrators' => $sendMailRequest->toAdministrators,
                'patientId' => $sendMailRequest->patientId
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $role,
                $studyName,
                $sendMailRequest->visitId,
                Constants::TRACKER_SEND_MESSAGE,
                $actionsDetails
            );

            $sendMailResponse->status = 200;
            $sendMailResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $sendMailResponse->body = $e->getErrorBody();
            $sendMailResponse->status = $e->statusCode;
            $sendMailResponse->statusText = $e->statusText;
        }
    }

    private function checkEmpty($inputData, $name)
    {
        if (empty($inputData)) {
            throw new GaelOBadRequestException('Request missing ' . $name);
        }
    }

    private function checkAuthorization(int $userId, string $study, string $role)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($study);
        if (!$this->authorizationStudyService->isAllowedStudy($role)) {
            throw new GaelOForbiddenException();
        }
    }

    private function checkAuthorizationAdmin(int $currentUserId)
    {
        $this->authorizationUserService->setUserId($currentUserId);
        if (!$this->authorizationUserService->isAdmin($currentUserId)) {
            throw new GaelOForbiddenException();
        }
    }
}
