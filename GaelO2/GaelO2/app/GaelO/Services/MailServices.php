<?php

namespace App\GaelO\Services;

use App\GaelO\Interfaces\MailInterface;
use App\GaelO\Adapters\SendEmailAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Constants\MailConstants;
use App\GaelO\Repositories\ReviewRepository;

Class MailServices extends SendEmailAdapter {

    private MailInterface $mailInterface;
    private UserRepository $userRepository;
    private ReviewRepository $reviewRepository;

    public function __construct(MailInterface $mailInterface, UserRepository $userRepository, ReviewRepository $reviewRepository) {
        $this->mailInterface = $mailInterface;
        $this->userRepository = $userRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function getUserEmail(int $userId) : string{
        return $this->userRepository->find($userId)['email'];
    }

    public function getUserName(int $userId){
        $userEntity = $this->userRepository->find($userId);
        return $userEntity['firstname'].' '.$userEntity['lastname'];
    }

    public function getAdminsEmails() : array {
        $adminsEmails = $this->userRepository->getAdministratorsEmails();
        return $adminsEmails;
    }

    public function getInvestigatorOfCenterInStudy(String $study, String $center, ?String $job=null) : array {
        $emails = $this->userRepository->getInvestigatorsStudyFromCenterEmails($study, $center, $job);
        return $emails;
    }

    /**
     * Parameters in associative array : name, email, center, request
     */
    public function sendRequestMessage(array $parameters) : void {
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
    public function sendResetPasswordMessage(string $name, string $username, string $newPassword, string $email) : void {
        $parameters = [
            'name'=> $name,
            'username'=> $username,
            'newPassword'=> $newPassword,
            'email'=> $email
        ];
        $this->mailInterface->setTo([$parameters['email']]);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_RESET_PASSWORD);
        $this->mailInterface->send();

    }

    public function sendAccountBlockedMessage(String $username, String $email) : void {
        //Get all studies with role for the user
        $studies = $this->userRepository->getAllStudiesWithRoleForUser($username);
        $parameters = [
            'name'=>'user',
            'username'=>$username,
            'studies'=>$studies
        ];
        //Send to user and administrators
        $this->mailInterface->setTo( [$email, ...$this->getAdminsEmails()] );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_BLOCKED_ACCOUNT);
        $this->mailInterface->send();

    }

    public function sendAdminConnectedMessage(String $username, String $remoteAddress) : void {
        $parameters = [
            'name'=> 'Administrator',
            'username'=>$username,
            'remoteAddress'=>$remoteAddress
        ];
        //Send to administrators
        $this->mailInterface->setTo( $this->getAdminsEmails() );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADMIN_LOGGED);
        $this->mailInterface->send();

    }

    public function sendCreatedAccountMessage(string $userEmail, String $name, String $username, String $password) : void {

        $parameters = [
            'name'=> $name,
            'username'=>$username,
            'password'=>$password
        ];

        //Send to administrators
        $this->mailInterface->setTo( [$userEmail] );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_USER_CREATED);
        $this->mailInterface->send();

    }

    public function sendForbiddenResetPasswordDueToDeactivatedAccount(String $userEmail, String $username) : void{

        $studies = $this->userRepository->getAllStudiesWithRoleForUser($username);

        $parameters = [
            'name' => 'user',
            'username'=>$username,
            'studies'=>$studies
        ];

        //Send to administrators
        $this->mailInterface->setTo( [$userEmail] );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CHANGE_PASSWORD_DEACTIVATED);
        $this->mailInterface->send();

    }

    public function sendImportPatientMessage(String $study, array $successList, array $failList){

        $parameters = [
            'name' => 'supervisor',
            'study' => $study,
            'successList'=>$successList,
            'failList'=>$failList
        ];

        //Send to supervisors of the study
        $this->mailInterface->setTo( $this->userRepository->getUsersEmailsByRolesInStudy($study, Constants::ROLE_SUPERVISOR) );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_IMPORT_PATIENT);
        $this->mailInterface->send();

    }

    public function sendUploadedVisitMessage(int $uploadUserId, string $study, int $patientCode, string $visitType, bool $qcNeeded){

        $parameters = [
            'name' => $this->getUserName($uploadUserId),
            'study' => $study,
            'patientCode'=>$patientCode,
            'visitType'=>$visitType
        ];

        //Send to supervisors and monitors of the study
        $destinators = [
            $this->getUserEmail($uploadUserId),
            ...$this->userRepository->getUsersEmailsByRolesInStudy($study, Constants::ROLE_SUPERVISOR),
            ...$this->userRepository->getUsersEmailsByRolesInStudy($study, Constants::ROLE_MONITOR)
        ];
        //If QC is awaiting add controllers
        if ($qcNeeded)  {
            $destinators = [
                ...$destinators,
                ...$this->userRepository->getUsersEmailsByRolesInStudy($study, Constants::ROLE_CONTROLLER)
            ];
        }

        $this->mailInterface->setTo( $destinators );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UPLOADED_VISIT);
        $this->mailInterface->send();

    }

    public function sendAvailableReviewMessage(string $study, int $patientCode, string $visitType){

        $parameters = [
            'study' => $study,
            'patientCode'=>$patientCode,
            'visitType'=>$visitType
        ];

        $this->mailInterface->setTo( $this->userRepository->getUsersEmailsByRolesInStudy($study, Constants::ROLE_REVIEWER) );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_REVIEW_READY);
        $this->mailInterface->send();

    }

    public function sendValidationFailMessage(int $visitId, string $patientCode, string $visitType,
                string $studyName, string $zipPath, int $userId, string $errorMessage){

        $parameters = [
            'name'=> 'User',
            'idVisit' => $visitId,
            'patientCode'=>$patientCode,
            'visitType'=>$visitType,
            'study'=> $studyName,
            'zipPath'=> $zipPath,
            'userId'=> $userId,
            'errorMessage'=>$errorMessage
        ];

        $this->mailInterface->setTo( $this->userRepository->getAdministratorsEmails() );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UPLOAD_FAILURE);
        $this->mailInterface->send();
    }

    public function sendQcDecisionMessage(int $uploaderId, int $controllerId, string $studyName, int $centerCode, string $qcDecision, int $patientCode,
                       string $visitModality,string $visitType, string $formDecision, string $imageDecision, string $formComment, string $imageComment ){

        $parameters = [
            'name'=> 'User',
            'controlDecision'=> $qcDecision,
            'study' => $studyName,
            'patientCode'=> $patientCode,
            'visitModality'=>$visitModality,
            'visitType'=> $visitType,
            'formDecision'=> $formDecision,
            'formComment'=> $formComment,
            'imageDecision'=> $imageDecision,
            'imageComment'=> $imageComment
        ];


        $this->mailInterface->setTo( [
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR),
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

    public function sendCorrectiveActionMessage(int $currentUserId, string $studyName, bool $correctionApplyed, bool $patientCode, string $visitModality, string $visitType){


        $parameters = [
            'name'=> 'User',
            'correctionApplyed'=> $correctionApplyed,
            'study' => $studyName,
            'patientCode'=> $patientCode,
            'visitModality'=>$visitModality,
            'visitType'=> $visitType
        ];

        $this->mailInterface->setTo( [
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_CONTROLLER),
            $this->getUserEmail($currentUserId),
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CORRECTIVE_ACTION);
        $this->mailInterface->send();

    }

    public function sendUnlockMessage(int $currentUserId, string $role, string $username, string $studyName, int $patientCode, string $message, string $visitType){

        $parameters = [
            'name'=> 'Supervisor',
            'role'=> $role,
            'username'=> $username,
            'study' => $studyName,
            'patientCode'=> $patientCode,
            'message'=>$message,
            'visitType'=> $visitType
        ];

        $this->mailInterface->setTo([
            $this->getUserEmail($currentUserId),
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
        ]);

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_UNLOCK_FORM);
        $this->mailInterface->send();

    }

    public function sendAwaitingAdjudicationMessage(string $studyName, int $patientCode, string $visitType, int $visitId){

        $parameters = [
            'name'=> 'User',
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType
        ];

        //Get All Users with Reviwers in this study
        $reviewersUsers = $this->userRepository->getUsersByRolesInStudy($studyName, Constants::ROLE_REVIEWER);

        //Get All Reviews of this visit
        $reviews = $this->reviewRepository->getValidatedReviewsForStudyVisit($studyName, $visitId);
        $reviewerDoneUserIdArray = array_map(function($user){
            return $user['user_id'];
        }, $reviews);

        //Select users who didn't validate review form of this visit
        $availableReviewers = array_filter($reviewersUsers, function($user) use ($reviewerDoneUserIdArray) {
            return !in_array($user['id'], $reviewerDoneUserIdArray);
        });

        //Build email list
        $availableReviewersEmails = array_map(function($user){
            return $user['email'];
        }, $availableReviewers);

        $this->mailInterface->setTo( [
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
            ...$availableReviewersEmails,
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_ADJUDICATION);
        $this->mailInterface->send();

    }

    public function sendVisitConcludedMessage(int $uploaderId, string $studyName, int $patientCode, string $visitType, string $conclusionValue){

        $parameters = [
            'name'=> 'User',
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'conclusionValue' => $conclusionValue
        ];

        $this->mailInterface->setTo( [
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR),
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR),
            $this->getUserEmail($uploaderId)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_CONCLUSION);
        $this->mailInterface->send();


    }

    public function sendDeleteFormMessage(bool $investigatorForm, int $formOwnerId, string $studyName, int $patientCode, string $visitType){

        $parameters = [
            'name' => $this->getUserName($formOwnerId),
            'study' => $studyName,
            'patientCode' => $patientCode,
            'visitType' => $visitType,
            'formType' => $investigatorForm ? 'Investigator' : 'Review'
        ];

        $this->mailInterface->setTo( [
            $this->getUserEmail($formOwnerId)
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->setBody(MailConstants::EMAIL_DELETED_FORM);
        $this->mailInterface->send();

    }

}
