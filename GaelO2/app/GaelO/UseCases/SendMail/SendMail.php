<?php

namespace App\GaelO\UseCases\SendMail;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\MailServices;

class SendMail
{

    private MailServices $mailService;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        MailServices $mailService,
        AuthorizationStudyService $authorizationStudyService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->mailService = $mailService;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(SendMailRequest $sendMailRequest, SendMailResponse $sendMailResponse)
    {

        try {
            $this->checkEmpty($sendMailRequest->role, 'role');

            if ($sendMailRequest->role !== Constants::ROLE_ADMINISTRATOR) $this->checkAuthorization($sendMailRequest->currentUserId, $sendMailRequest->study, $sendMailRequest->role);

            $this->checkEmpty($sendMailRequest->subject, 'subject');
            $this->checkEmpty($sendMailRequest->content, 'content');

            //EO split 1 use case par role ? Dissocier send mail de patients creation request ?
            switch ($sendMailRequest->role) {
                case Constants::ROLE_SUPERVISOR:
                    if ($sendMailRequest->toAdministrators)
                        $this->mailService->sendMailToAdministrators(
                            $sendMailRequest->currentUserId,
                            $sendMailRequest->study,
                            $sendMailRequest->subject,
                            $sendMailRequest->content,
                        );
                    else {
                        $this->checkEmpty($sendMailRequest->userIds, 'recipient');
                        $this->mailService->sendMailToUser(
                            $sendMailRequest->currentUserId,
                            $sendMailRequest->userIds,
                            $sendMailRequest->study,
                            $sendMailRequest->subject,
                            $sendMailRequest->content
                        );
                    }
                    break;
                case Constants::ROLE_ADMINISTRATOR:
                    $this->checkEmpty($sendMailRequest->userIds, 'recipient');
                    $this->mailService->sendMailToUser(
                        $sendMailRequest->currentUserId,
                        $sendMailRequest->userIds,
                        null,
                        $sendMailRequest->subject,
                        $sendMailRequest->content
                    );
                    break;
                default:
                    if (isset($sendMailRequest->userIds)) throw new GaelOForbiddenException();
                    if (isset($sendMailRequest->patients) && count(json_decode($sendMailRequest->patients, true)) === 0) throw new GaelOBadRequestException('Request missing patient list');
                    $this->mailService->sendMailToSupervisors(
                        $sendMailRequest->currentUserId,
                        $sendMailRequest->study,
                        $sendMailRequest->subject,
                        $sendMailRequest->content,
                        $sendMailRequest->patientId,
                        $sendMailRequest->visitId,
                        $sendMailRequest->patients,
                    );
            }

            $actionsDetails = [
                'subject' => $sendMailRequest->subject,
                'content' => $sendMailRequest->content,
                'to_user_ids' => $sendMailRequest->userIds,
                'to_administrators' => $sendMailRequest->toAdministrators,
                'patientId' => $sendMailRequest->patientId,
                'patients' => $sendMailRequest->patients
            ];

            $this->trackerRepositoryInterface->writeAction(
                $sendMailRequest->currentUserId,
                $sendMailRequest->role,
                $sendMailRequest->study,
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

        return $sendMailResponse;
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
}
