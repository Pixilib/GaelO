<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\MailConstants;
use App\GaelO\Interfaces\Adapters\MailerInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Repositories\ReviewRepository;

class MailServices
{

    private MailerInterface $mailInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private ReviewRepository $reviewRepositoryInterface;

    public function __construct(MailerInterface $mailInterface, UserRepositoryInterface $userRepositoryInterface, ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->mailInterface = $mailInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function getUserEmail(int $userId): string
    {
        return $this->userRepositoryInterface->find($userId)['email'];
    }

    public function getUserName(int $userId)
    {
        $userEntity = $this->userRepositoryInterface->find($userId);
        return $userEntity['firstname'] . ' ' . $userEntity['lastname'];
    }

    public function getAdminsEmails(): array
    {
        $adminsEmails = $this->userRepositoryInterface->getAdministratorsEmails();
        return $adminsEmails;
    }

    public function getInvestigatorOfCenterInStudy(String $study, String $center, ?String $job = null): array
    {
        $emails = $this->userRepositoryInterface->getInvestigatorsStudyFromCenterEmails($study, $center, $job);
        return $emails;
    }

    /**
     * Parameters in associative array : name, email, center, request
     */
    public function sendRequestMessage(array $parameters): void
    {
        $destinators = [...$this->getAdminsEmails(), $parameters['email']];
        $this->mailInterface->setTo($destinators);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REQUEST);
        $this->mailInterface->send();
    }

    /**
     * Parameter in associative array : name, username, newPassword, email
     */
    public function sendResetPasswordMessage(string $name, string $username, string $newPassword, string $email): void
    {
        $parameters = [
            'name' => $name,
            'username' => $username,
            'newPassword' => $newPassword,
            'email' => $email
        ];
        $this->mailInterface->setTo([$parameters['email']]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_RESET_PASSWORD);
        $this->mailInterface->send();
    }

    public function sendAccountBlockedMessage(String $username, String $email, int $userId): void
    {
        //Get all studies with role for the user
        $studiesEntities = $this->userRepositoryInterface->getStudiesOfUser($userId);

        $studies = array_map(function ($study) {
            return $study['name'];
        }, $studiesEntities);

        $parameters = [
            'name' => 'user',
            'username' => $username,
            'studies' => $studies
        ];
        //Send to user and administrators
        $this->mailInterface->setTo([$email, ...$this->getAdminsEmails()]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_BLOCKED_ACCOUNT);
        $this->mailInterface->send();
    }

    public function sendAdminConnectedMessage(String $username, String $remoteAddress): void
    {
        $parameters = [
            'name' => 'Administrator',
            'username' => $username,
            'remoteAddress' => $remoteAddress
        ];
        //Send to administrators
        $this->mailInterface->setTo($this->getAdminsEmails());
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADMIN_LOGGED);
        $this->mailInterface->send();
    }

    public function sendCreatedAccountMessage(string $userEmail, String $name, String $username, String $password): void
    {

        $parameters = [
            'name' => $name,
            'username' => $username,
            'password' => $password
        ];

        //Send to administrators
        $this->mailInterface->setTo([$userEmail]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER_CREATED);
        $this->mailInterface->send();
    }

    public function sendForbiddenResetPasswordDueToDeactivatedAccount(String $userEmail, String $username, int $userId): void
    {

        $parameters = [
            'name' => 'user',
            'username' => $username
        ];

        //Send to administrators
        $this->mailInterface->setTo([$userEmail, ...$this->getAdminsEmails()]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CHANGE_PASSWORD_DEACTIVATED);
        $this->mailInterface->send();
    }

    public function sendImportPatientMessage(String $study, array $successList, array $failList)
    {

        $parameters = [
            'name' => 'supervisor',
            'study' => $study,
            'successList' => $successList,
            'failList' => $failList
        ];

        //Send to supervisors of the study
        $this->mailInterface->setTo($this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, Constants::ROLE_SUPERVISOR));
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_IMPORT_PATIENT);
        $this->mailInterface->send();
    }

    public function sendUploadedVisitMessage(int $visitId, int $uploadUserId, string $study, int $patientCode, string $visitType, bool $qcNeeded)
    {

        $parameters = [
            'name' => $this->getUserName($uploadUserId),
            'study' => $study,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        //Send to supervisors and monitors of the study
        $destinators = [
            $this->getUserEmail($uploadUserId),
            ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, Constants::ROLE_SUPERVISOR),
            ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, Constants::ROLE_MONITOR)
        ];
        //If QC is awaiting add controllers
        if ($qcNeeded) {
            $destinators = [
                ...$destinators,
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, Constants::ROLE_CONTROLLER)
            ];
        }

        $this->mailInterface->setTo($destinators);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UPLOADED_VISIT);
        $this->mailInterface->send();
    }

    public function sendAvailableReviewMessage(int $visitId, string $study, int $patientCode, string $visitType)
    {

        $parameters = [
            'study' => $study,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo($this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, Constants::ROLE_REVIEWER));
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REVIEW_READY);
        $this->mailInterface->send();
    }

    public function sendValidationFailMessage(
        int $visitId,
        string $patientCode,
        string $visitType,
        string $studyName,
        string $zipPath,
        int $userId,
        string $errorMessage
    ) {

        $parameters = [
            'name' => 'User',
            'idVisit' => $visitId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'study' => $studyName,
            'zipPath' => $zipPath,
            'userId' => $userId,
            'errorMessage' => $errorMessage
        ];

        $this->mailInterface->setTo($this->userRepositoryInterface->getAdministratorsEmails());
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UPLOAD_FAILURE);
        $this->mailInterface->send();
    }

    public function sendQcDecisionMessage(
        int $visitId,
        int $uploaderId,
        int $controllerId,
        string $studyName,
        int $centerCode,
        string $qcDecision,
        int $patientCode,
        string $visitModality,
        string $visitType,
        string $formDecision,
        string $imageDecision,
        string $formComment,
        string $imageComment
    ) {

        $parameters = [
            'name' => 'User',
            'controlDecision' => $qcDecision,
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitModality' => $visitModality,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formDecision' => $formDecision,
            'formComment' => $formComment,
            'imageDecision' => $imageDecision,
            'imageComment' => $imageComment
        ];


        $this->mailInterface->setTo(
            [
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR),
                $this->getUserEmail($uploaderId),
                $this->getUserEmail($controllerId),
                ...$this->getInvestigatorOfCenterInStudy($studyName, $centerCode)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_QC_DECISION);
        $this->mailInterface->send();
    }

    public function sendCorrectiveActionMessage(int $visitId, int $currentUserId, string $studyName, bool $correctionApplied, bool $patientCode, string $visitModality, string $visitType)
    {


        $parameters = [
            'name' => 'User',
            'correctionApplied' => $correctionApplied,
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitModality' => $visitModality,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo(
            [
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_CONTROLLER),
                $this->getUserEmail($currentUserId),
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CORRECTIVE_ACTION);
        $this->mailInterface->send();
    }

    public function sendUnlockMessage(int $visitId, int $currentUserId, string $role, string $username, string $studyName, int $patientCode, string $messages, string $visitType)
    {

        $parameters = [
            'name' => 'Supervisor',
            'role' => $role,
            'username' => $username,
            'study' => $studyName,
            'patientCode' => $patientCode,
            'messages' => $messages,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo([
            $this->getUserEmail($currentUserId),
            ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
        ]);

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_REQUEST);
        $this->mailInterface->send();
    }

    public function sendAwaitingAdjudicationMessage(string $studyName, int $patientCode, string $visitType, int $visitId)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        //Get All Users with Reviwers in this study
        $reviewersUsers = $this->userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_REVIEWER);

        //Get All Reviews of this visit
        $reviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($studyName, $visitId, true);
        $reviewerDoneUserIdArray = array_map(function ($user) {
            return $user['user_id'];
        }, $reviews);

        //Select users who didn't validate review form of this visit
        $availableReviewers = array_filter($reviewersUsers, function ($user) use ($reviewerDoneUserIdArray) {
            return !in_array($user['id'], $reviewerDoneUserIdArray);
        });

        //Build email list
        $availableReviewersEmails = array_map(function ($user) {
            return $user['email'];
        }, $availableReviewers);

        $this->mailInterface->setTo(
            [
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
                ...$availableReviewersEmails,
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADJUDICATION);
        $this->mailInterface->send();
    }

    public function sendVisitConcludedMessage(int $visitId, int $uploaderId, string $studyName, int $patientCode, string $visitType, string $conclusionValue)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'conclusionValue' => $conclusionValue
        ];

        $this->mailInterface->setTo(
            [
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR),
                $this->getUserEmail($uploaderId)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CONCLUSION);
        $this->mailInterface->send();
    }

    public function sendDeleteFormMessage( int $visitId, bool $investigatorForm, int $formOwnerId, string $studyName, int $patientCode, string $visitType)
    {

        $parameters = [
            'name' => $this->getUserName($formOwnerId),
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formType' => $investigatorForm ? 'Investigator' : 'Review'
        ];

        $this->mailInterface->setTo(
            [
                $this->getUserEmail($formOwnerId)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_DELETED_FORM);
        $this->mailInterface->send();
    }

    public function sendUnlockFormMessage(int $visitId, bool $investigatorForm, int $formOwnerId, string $studyName, int $patientCode, string $visitType)
    {

        $parameters = [
            'name' => $this->getUserName($formOwnerId),
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formType' => $investigatorForm ? 'Investigator' : 'Review'
        ];

        $this->mailInterface->setTo(
            [
                $this->getUserEmail($formOwnerId)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_FORM);
        $this->mailInterface->send();
    }


    public function sendVisitNotDoneMessage(int $visitId, string $studyName, int $patientCode, string $visitType, string $reasonNotDone, int $userId)
    {

        $parameters = [
            'patientCode' => $patientCode,
            'study' => $studyName,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'creatorUser' => $this->getUserName($userId)
        ];

        $this->mailInterface->setTo(
            $this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_VISIT_NOT_DONE);
        $this->mailInterface->send();
    }

    /**
     * Parameters in associative array: study, subject, content, centers
     */
    public function sendReminderToInvestigators(array $parameters)
    {
        $centerCode = $parameters['centerCode'];

        $parameters = [
            'study' => $parameters['study'],
            'subject' => $parameters['subject'],
            'content' => $parameters['content']
        ];

        $this->mailInterface->setTo($this->getInvestigatorOfCenterInStudy($parameters['study'], $centerCode));
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REMINDER);
        $this->mailInterface->send();
    }


    /**
     * Parameters in associative array: study, subject, content, role
     */
    public function sendReminder(array $parameters)
    {
        $role = $parameters['role'];
     
        $parameters = [
            'study' => $parameters['study'],
            'subject' => $parameters['subject'],
            'content' => $parameters['content']
        ];

        $this->mailInterface->setTo(
            $this->userRepositoryInterface->getUsersEmailsByRolesInStudy($parameters['study'], $role)
        );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REMINDER);
        $this->mailInterface->send();
    }

}
