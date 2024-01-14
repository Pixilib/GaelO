<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\MailConstants;
use App\GaelO\Interfaces\Adapters\MailerInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\MailService\MailListBuilder;

class MailServices
{

    private MailerInterface $mailInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(
        MailerInterface $mailInterface,
        UserRepositoryInterface $userRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface
    ) {
        $this->mailInterface = $mailInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function getStudyContactEmail(string $studyName): string
    {
        $studyEntity = $this->studyRepositoryInterface->find($studyName);
        return $studyEntity->contactEmail;
    }


    public function getUserName(int $userId)
    {
        $userEntity = $this->userRepositoryInterface->find($userId);
        return $userEntity['firstname'] . ' ' . $userEntity['lastname'];
    }

    public function getUserEmail(int $userId)
    {
        return $this->userRepositoryInterface->find($userId)['email'];
    }

    public function sendRequestMessage(string $name, string $email, string $center, string $request): void
    {
        $parameters = [
            'name' => $name,
            'email' => $email,
            'center' => $center,
            'request' => $request
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withAdminsEmails();
        $destinators = [...$mailListBuilder->get(), $email];

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
            'name' => 'User',
            'email' => $email,
            'studies' => $studies
        ];
        //Send to user and administrators
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withAdminsEmails();
        $destinators = [...$mailListBuilder->get(), $email];

        $this->mailInterface->setTo($destinators);
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
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withAdminsEmails();

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADMIN_LOGGED);
        $this->mailInterface->send();
    }

    public function sendForbiddenResetPasswordDueToDeactivatedAccount(String $userEmail, String $lastname, string $firstname): void
    {

        $parameters = [
            'name' => 'User',
            'email' => $userEmail,
            'lastname' => $lastname,
            'firstname' => $firstname
        ];

        //Send to administrators
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withAdminsEmails();

        $this->mailInterface->setTo([$userEmail, ...$mailListBuilder->get()]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CHANGE_PASSWORD_DEACTIVATED);
        $this->mailInterface->send();
    }

    public function sendImportPatientMessage(String $studyName, string $contactEmail, array $successList, array $failList)
    {

        $parameters = [
            'name' => 'Supervisor',
            'study' => $studyName,
            'successList' => $successList,
            'failList' => $failList
        ];

        //Send to supervisors of the study
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR);

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($contactEmail);
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_IMPORT_PATIENT);
        $this->mailInterface->send();
    }

    public function sendUploadedVisitMessage(array $emails, int $visitId, string $studyName, string $patientId, string $patientCode, string $visitType)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo($emails);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UPLOADED_VISIT);
        $this->mailInterface->send();
    }

    public function sendReviewReadyMessage(array $emails, int $visitId, string $studyName, string $patientId, string $patientCode, string $visitType)
    {

        $parameters = [
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo($emails);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REVIEW_READY);
        $this->mailInterface->send();
    }

    public function sendValidationFailMessage(
        int $visitId,
        string $patientId,
        string $visitType,
        string $studyName,
        ?string $zipPath,
        int $userId,
        string $errorMessage
    ) {

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);

        $parameters = [
            'name' => 'User',
            'idVisit' => $visitId,
            'patientId' => $patientId,
            'visitType' => $visitType,
            'study' => $studyName,
            'zipPath' => $zipPath ?? "None",
            'userId' => $userId,
            'userEmail' => $this->getUserEmail($userId),
            'errorMessage' => $errorMessage
        ];

        $this->mailInterface->setTo($mailListBuilder->withAdminsEmails()->get());
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UPLOAD_FAILURE);
        $this->mailInterface->send();
    }

    public function sendQcDecisionMessage(
        array $emails,
        int $visitId,
        string $studyName,
        string $qcDecision,
        string $patientId,
        string $patientCode,
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
            'patientCode' => $patientCode,
            'visitModality' => $visitModality,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formDecision' => $formDecision,
            'formComment' => $formComment,
            'imageDecision' => $imageDecision,
            'imageComment' => $imageComment
        ];

        $this->mailInterface->setTo($emails);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_QC_DECISION);
        $this->mailInterface->send();
    }

    public function sendCorrectiveActionMessage(array $emails, int $visitId, string $studyName, bool $correctionApplied, string $patientId, string $patientCode, string $visitModality, string $visitType)
    {
        $parameters = [
            'name' => 'User',
            'correctionApplied' => $correctionApplied,
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitModality' => $visitModality,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo($emails);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CORRECTIVE_ACTION);
        $this->mailInterface->send();
    }

    public function sendUnlockMessage(int $visitId, int $currentUserId, string $role, string $studyName, string $patientId, string $patientCode, string $messages, string $visitType)
    {

        $parameters = [
            'name' => 'Supervisor',
            'role' => $role,
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'messages' => $messages,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUserEmail($currentUserId)
            ->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR);

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_REQUEST);
        $this->mailInterface->send();
    }

    public function sendUnlockQCMessage(int $visitId, int $currentUserId, string $studyName, string $patientId, string $patientCode, string $messages, string $visitType)
    {

        $parameters = [
            'name' => 'reviewer',
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'messages' => $messages,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
            ->withUserEmail($currentUserId);

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_QC_REQUEST);
        $this->mailInterface->send();
    }

    public function sendAwaitingAdjudicationMessage(array $emails, string $studyName, string $patientId, string $patientCode, string $visitType, int $visitId)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId
        ];

        $this->mailInterface->setTo($emails);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADJUDICATION);
        $this->mailInterface->send();
    }

    public function sendVisitConcludedMessage(array $emails, int $visitId, string $studyName, string $patientId, string $patientCode, string $visitType, string $conclusionValue)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'conclusionValue' => $conclusionValue
        ];

        $this->mailInterface->setTo($emails);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CONCLUSION);
        $this->mailInterface->send();
    }

    public function sendDeleteFormMessage(int $visitId, bool $investigatorForm, int $formOwnerId, string $studyName, string $patientId, string $patientCode, string $visitType)
    {

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);

        $parameters = [
            'name' => $this->getUserName($formOwnerId),
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formType' => $investigatorForm ? 'Investigator' : 'Review'
        ];

        $this->mailInterface->setTo($mailListBuilder->withUserEmail($formOwnerId)->get());
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_DELETED_FORM);
        $this->mailInterface->send();
    }

    public function sendUnlockedFormMessage(int $visitId, bool $investigatorForm, int $requestingUserId, string $studyName, string $patientId, string $patientCode, string $visitType)
    {
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);

        $parameters = [
            'name' => $this->getUserName($requestingUserId),
            'study' => $studyName,
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'formType' => $investigatorForm ? 'Investigator' : 'Review'
        ];

        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR);

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_FORM);
        $this->mailInterface->send();
    }


    public function sendVisitNotDoneMessage(int $visitId, string $studyName, string $patientId, string $patientCode, string $visitType, string $reasonNotDone, int $userId)
    {
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);

        $parameters = [
            'patientId' => $patientId,
            'patientCode' => $patientCode,
            'study' => $studyName,
            'visitType' => $visitType,
            'visitId' => $visitId,
            'notDoneReason' => $reasonNotDone,
            'creatorUser' => $this->getUserName($userId)
        ];

        $this->mailInterface->setTo(
            $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)->get()
        );
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_VISIT_NOT_DONE);
        $this->mailInterface->send();
    }

    public function sendReminder(string $senderId, array $userIds, string $studyName, string $subject, string $content)
    {

        $parameters = [
            'study' => $studyName,
            'subject' => $subject,
            'content' => $content
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        foreach ($userIds as $userId) {
            return $mailListBuilder->withUserEmail($userId);
        };
        $mailListBuilder->withUserEmail($senderId);

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($this->getUserEmail($senderId));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REMINDER);
        $this->mailInterface->send();
    }

    public function sendPatientCreationRequest(int $senderId, string $studyName, string $content, array $patients)
    {
        $parameters = [
            'study' => $studyName,
            'content' => $content,
            'patients' => $patients
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR);

        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($this->getUserEmail($senderId));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REQUEST_PATIENT_CREATION);
        $this->mailInterface->send();
    }

    public function sendMailToSupervisors(?int $senderId, string $studyName, string $subject, string $content, ?string $patientId, ?int $visitId, ?string $patientCode, ?string $visitType)
    {

        $parameters = [
            'study' => $studyName,
            'subject' => $subject,
            'content' => $content,
            'patientId' => $patientId,
            'visitId' => $visitId,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'canReply' => true
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR);

        $this->mailInterface->setTo($mailListBuilder->get());
        if ($senderId !== null) {
            $this->mailInterface->setReplyTo($this->getUserEmail($senderId));
        }
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendMailToEmails(?int $senderId, array $toEmails, ?string $studyName, string $subject, string $content)
    {
        $parameters = [
            'study' => $studyName,
            'subject' => $subject,
            'content' => $content,
            'canReply' => true
        ];

        $this->mailInterface->setTo($toEmails);
        if ($senderId !== null) {
            $this->mailInterface->setReplyTo($this->getUserEmail($senderId));
        }
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendMailToUser(?int $senderId, array $userIds, ?string $studyName, string $subject, string $content)
    {
        $parameters = [
            'study' => $studyName,
            'subject' => $subject,
            'content' => $content,
            'canReply' => true
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        foreach ($userIds as $userId) {
            $mailListBuilder->withUserEmail($userId);
        }
        $this->mailInterface->setTo($mailListBuilder->get());
        if ($senderId !== null) {
            $this->mailInterface->setReplyTo($this->getUserEmail($senderId));
        }
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendMailToAdministrators(int $senderId, string $studyName, string $subject, string $content)
    {

        $parameters = [
            'study' => $studyName,
            'subject' => $subject,
            'content' => $content,
            'canReply' => true
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withAdminsEmails();
        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setReplyTo($this->getUserEmail($senderId));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER);
        $this->mailInterface->send();
    }

    public function sendCreatedUserMessage(string $email)
    {
        $parameters = [
            'name' => 'User',
            'email' => $email
        ];

        $this->mailInterface->setTo([$email]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER_CREATED);
        $this->mailInterface->send();
    }

    public function sendMagicLink(int $targetedUserId, string $studyName, string $url, string $role, int $patientCode, string $visitType = null)
    {

        $parameters = [
            'name' => 'User',
            'study' => $studyName,
            'url' => $url,
            'role' => $role,
            'patientCode' => $patientCode,
            'visitType' => $visitType
        ];

        $this->mailInterface->setTo([$this->getUserEmail($targetedUserId)]);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_MAGIC_LINK);
        $this->mailInterface->send();
    }

    public function sendQcReport(string $studyName, string $visitType, string $patientCode, string $magicLink, string $controllerEmail)
    {

        $parameters = [
            'study' => $studyName,
            'visitType' => $visitType,
            'patientCode' => $patientCode,
            'magicLink' => $magicLink,
        ];

        $this->mailInterface->setTo([$controllerEmail]);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_QC_REPORT);
        $this->mailInterface->send();
    }

    public function sendRadiomicsReport(string $studyName, string $patientCode, string $visitType, string $visitDate, string $imagePath, array $stats, array $emailList)
    {
        $parameters = [
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'studyName' => $studyName,
            'visitDate' => $visitDate,
            'image_path' => [$imagePath],
            'stats' => $stats
        ];

        $this->mailInterface->setTo($emailList);
        $this->mailInterface->setReplyTo($this->getStudyContactEmail($studyName));
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_RADIOMICS_REPORT);
        $this->mailInterface->send();
    }


    public function sendJobFailure(string $jobType, array $details, string $errorMessage)
    {
        $parameters = [
            'jobType' => $jobType,
            'details' => $details,
            'errorMessage' => $errorMessage
        ];

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface, $this->studyRepositoryInterface);
        $mailListBuilder->withAdminsEmails();
        $this->mailInterface->setTo($mailListBuilder->get());
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_JOB_FAILURE);
        $this->mailInterface->send();
    }
}
