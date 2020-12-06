<?php

namespace App\GaelO\Services;

use App\GaelO\Interfaces\MailInterface;
use App\GaelO\Adapters\SendEmailAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Constants\MailConstants;

Class MailServices extends SendEmailAdapter {

    public function __construct(MailInterface $mailInterface, UserRepository $userRepository) {
        $this->mailInterface = $mailInterface;
        $this->userRepository = $userRepository;
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
        $destinators = [$this->getAdminsEmails(), $parameters['email']];
        $this->mailInterface->setTo($destinators);
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_REQUEST);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_RESET_PASSWORD);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_BLOCKED_ACCOUNT);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_ADMIN_LOGGED);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_USER_CREATED);

    }

    public function sendForbiddenResetPasswordDueToDeactivatedAccount(String $userEmail, String $username, Array $studies) : void{

        $parameters = [
            'name' => 'user',
            'username'=>$username,
            'studies'=>$studies
        ];

        //Send to administrators
        $this->mailInterface->setTo( [$userEmail] );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_CHANGE_PASSWORD_DEACTIVATED);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_IMPORT_PATIENT);

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
                ...$this->userRepository->getUsersEmailsByRolesInStudy($study, Constants::ROLE_CONTROLER)
            ];
        }

        $this->mailInterface->setTo( $destinators );
        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_UPLOADED_VISIT);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_REVIEW_READY);

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
        $this->mailInterface->sendModel(MailConstants::EMAIL_UPLOAD_FAILURE);
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
        $this->mailInterface->sendModel(MailConstants::EMAIL_QC_DECISION);


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
            ...$this->userRepository->getUsersEmailsByRolesInStudy($studyName, Constants::ROLE_CONTROLER),
            $this->getUserEmail($currentUserId),
            ]
        );

        $this->mailInterface->setReplyTo();
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_CORRECTIVE_ACTION);

    }

}
