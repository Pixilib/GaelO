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
        print_r($emails);
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
     * Parameter in associative array : username, newPassword
     */
    public function sendResetPasswordMessage(array $parameters){
        $this->mailInterface->setTo([$parameters['email']]);
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_RESET_PASSWORD);

    }

}
