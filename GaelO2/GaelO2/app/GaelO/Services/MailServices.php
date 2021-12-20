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

    public function sendRequestMessage(string $name, string $email, string $center, string $request): void
    {
        $parameters = [
            'name' => $name,
            'email' => $email,
            'center' => $center,
            'request' => $request
        ];

        $destinators = [...$this->getAdminsEmails(), $email];
        $this->mailInterface->setTo($destinators);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REQUEST);
        $this->mailInterface->send();
    }

    public function sendAccountBlockedMessage(String $email, int $userId): void
    {
        //Get all studies with role for the user
        $studiesEntities = $this->userRepositoryInterface->getStudiesOfUser($userId);

        $studies = array_map(function ($study) {
            return $study['name'];
        }, $studiesEntities);

        $parameters = [
            'name' => 'user',
            'email' => $email,
            'studies' => $studies
        ];
        //Send to user and administrators
        $this->mailInterface->setTo([$email, ...$this->getAdminsEmails()]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_BLOCKED_ACCOUNT);
        $this->mailInterface->send();
    }

    public function sendAdminConnectedMessage(String $email, String $remoteAddress): void
    {
        $parameters = [
            'name' => 'Administrator',
            'email' => $email,
            'remoteAddress' => $remoteAddress
        ];
        //Send to administrators
        $this->mailInterface->setTo($this->getAdminsEmails());
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADMIN_LOGGED);
        $this->mailInterface->send();
    }

    public function sendForbiddenResetPasswordDueToDeactivatedAccount(String $userEmail, String $lastname, string $firstname): void
    {

        $parameters = [
            'name' => 'user',
            'email' => $userEmail,
            'lastname' => $lastname,
            'firstname' => $firstname
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

    public function sendUploadedVisitMessage(int $visitId, int $uploadUserId, string $study, string $patientId, string $visitType, bool $qcNeeded)
    {

        $parameters = [
            'name' => $this->getUserName($uploadUserId),
            'study' => $study,
            'patientId' => $patientId,
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

    public function sendAvailableReviewMessage(int $visitId, string $study, string $patientId, string $visitType)
    {

        $parameters = [
            'study' => $study,
            'patientId' => $patientId,
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
        string $patientId,
        string $visitType,
        string $studyName,
        string $zipPath,
        int $userId,
        string $errorMessage
    ) {

        $parameters = [
            'name' => 'User',
            'idVisit' => $visitId,
            'patientId' => $patientId,
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
        string $patientId,
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
            'patientId' => $patientId,
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

    public function sendCorrectiveActionMessage(int $visitId, int $currentUserId, string $studyName, bool $correctionApplied, string $patientId, string $visitModality, string $visitType)
    {


        $parameters = [
            'name' => 'User',
            'correctionApplied' => $correctionApplied,
            'study' => $studyName,
            'patientId' => $patientId,
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

    public function sendUnlockMessage(int $visitId, int $currentUserId, string $role, string $firstname, string $lastname, string $studyName, string $patientId, string $messages, string $visitType)
    {

        $parameters = [
            'name' => 'Supervisor',
            'role' => $role,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'study' => $studyName,
            'patientId' => $patientId,
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

    public function sendAwaitingAdjudicationMessage(string $studyName, string $patientId, string $visitType, int $visitId)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientId' => $patientId,
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

    public function sendVisitConcludedMessage(int $visitId, int $uploaderId, string $studyName, string $patientId, string $visitType, string $conclusionValue)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientId' => $patientId,
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

    public function sendDeleteFormMessage( int $visitId, bool $investigatorForm, int $formOwnerId, string $studyName, string $patientId, string $visitType)
    {

        $parameters = [
            'name' => $this->getUserName($formOwnerId),
            'study' => $studyName,
            'patientId' => $patientId,
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

    public function sendUnlockFormMessage(int $visitId, bool $investigatorForm, int $requestingUserId, string $studyName, string $patientId, string $visitType)
    {

        $parameters = [
            'name' => $this->getUserName($requestingUserId),
            'study' => $studyName,
            'patientId' => $patientId,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formType' => $investigatorForm ? 'Investigator' : 'Review'
        ];

        $this->mailInterface->setTo(
            [
                ...$this->userRepositoryInterface->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_FORM);
        $this->mailInterface->send();
    }


    public function sendVisitNotDoneMessage(int $visitId, string $studyName, string $patientId, string $visitType, string $reasonNotDone, int $userId)
    {

        $parameters = [
            'patientId' => $patientId,
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

    public function sendReminderToInvestigators(int $centerCode, string $study, string $subject, string $content)
    {
        $centerCode = $centerCode;

        $parameters = [
            'name' => 'Investigator',
            'study' => $study,
            'subject' => $subject,
            'content' => $content
        ];

        $this->mailInterface->setTo($this->getInvestigatorOfCenterInStudy($study, $centerCode));
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REMINDER);
        $this->mailInterface->send();
    }

    public function sendReminder(string $role, string $study, string $subject, string $content)
    {
        $role = $role;

        $parameters = [
            'name' => $role,
            'study' => $study,
            'subject' => $subject,
            'content' => $content
        ];

        $this->mailInterface->setTo(
            $this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, $role)
        );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REMINDER);
        $this->mailInterface->send();
    }

    public function sendMailToSupervisors(string $study, string $subject, string $content, ?string $patientId, ?int $visitId)
    {

        $parameters = [
            'study' => $study,
            'subject' => $subject,
            'content' => $content,
            'patientId' => $patientId,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo(
            $this->userRepositoryInterface->getUsersEmailsByRolesInStudy($study, Constants::ROLE_SUPERVISOR)
        );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendMailToUser(array $userIds, string $study, string $subject, string $content)
    {
        $parameters = [
            'study' => $study,
            'subject' => $subject,
            'content' => $content
        ];

        $this->mailInterface->setTo(
                array_map(function ($userId) {
                    return $this->getUserEmail($userId);
                }, $userIds)
        );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendMailToAdministrators(string $study, string $subject, string $content)
    {

        $parameters = [
            'study' => $study,
            'subject' => $subject,
            'content' => $content,
        ];

        $this->mailInterface->setTo(
            $this->userRepositoryInterface->getAdministratorsEmails()
        );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendCreatedUserMessage(string $email){
        $parameters = ['name' => 'user'];

        $this->mailInterface->setTo([$email]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER_CREATED);
        $this->mailInterface->send();
    }

    public function sendMagicLink(int $targetedUserId, string $level, int $ressourceId, string $url){

        $parameters = ['name' => 'user', 'url' => $url];

        $this->mailInterface->setTo([$this->getUserEmail($targetedUserId)]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_MAGIC_LINK);
        $this->mailInterface->send();

    }

}
