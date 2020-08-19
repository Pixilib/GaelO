<?php

namespace App\GaelO\Services\Mails;

use App\GaelO\Interfaces\MailInterface;
use App\GaelO\Adapters\SendEmailAdapter;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Constants\Mail_Constants;
use Mail_Constants as GlobalMail_Constants;

Class MailServices extends SendEmailAdapter {

    public function __construct(MailInterface $mailInterface, UserRepository $userRepository){
        $this->mailInterface = $mailInterface;
        $this->userRepository = $userRepository;
    }

    public function getAdminsEmails(){
        $adminsEmails = $this->userRepository->getAdministratorsEmails();
        return $adminsEmails;
    }

    public function sendRequestMessage(array $parameters){
        $adminMails = $this->getAdminsEmails();
        $this->mailInterface->setTo($adminMails);
        $this->mailInterface->setVariable($parameters);
        $this->mailInterface->sendModel(GlobalMail_Constants::EMAIL_REQUEST);

    }

}
