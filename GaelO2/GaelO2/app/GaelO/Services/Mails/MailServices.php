<?php

namespace App\GaelO\Services\Mails;

use App\GaelO\Interfaces\MailInterface;
use App\GaelO\Adapters\SendEmailAdapter;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Constants\MailConstants;
use Symfony\Component\Console\EventListener\ErrorListener;

Class MailServices extends SendEmailAdapter {

    public function __construct(MailInterface $mailInterface, UserRepository $userRepository){
        $this->mailInterface = $mailInterface;
        $this->userRepository = $userRepository;
    }

    public function getAdminsEmails(){
        $adminsEmails = $this->userRepository->getAdministratorsEmails();
        return $adminsEmails;
    }

    public function getInvestigatorOfCenterInStudy($study, $center, $job=null){
        $emails = $this->userRepository->getInvestigatorsStudyFromCenterEmails($study, $center, $job);
        return $emails;
    }

    /**
     * Parameters in associative array : name, email, center, request
     */
    public function sendRequestMessage(array $parameters){
        $destinators = [$this->getAdminsEmails(), $parameters['email']];
        $this->mailInterface->setTo($destinators);
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_REQUEST);

    }

    /**
     * Parameter in associative array : name, username, newPassword, email
     */
    public function sendResetPasswordMessage(string $name, string $username, string $newPassword, string $email){
        $parameters = [
            'name'=> $name,
            'username'=> $username,
            'newPassword'=> $newPassword,
            'email'=> $email
        ];
        $this->mailInterface->setTo([$parameters['email']]);
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_RESET_PASSWORD);

    }

    public function sendAccountBlockedMessage($username, $email){
        //Get all studies with role for the user
        $studies = $this->userRepository->getAllStudiesWithRoleForUser($username);
        $parameters = [
            'username'=>$username,
            'studies'=>$studies
        ];
        //Send to user and administrators
        $this->mailInterface->setTo( [$email, ...$this->getAdminsEmails()] );
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_BLOCKED_ACCOUNT);

    }

    public function sendAdminConnectedMessage($username, $remoteAddress){
        $parameters = [
            'username'=>$username,
            'remoteAddress'=>$remoteAddress
        ];
        //Send to administrators
        $this->mailInterface->setTo( $this->getAdminsEmails() );
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_ADMIN_LOGGED);

    }

}
