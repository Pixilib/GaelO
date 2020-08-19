<?php

namespace App\GaelO\Services\Mails;

use App\GaelO\Interfaces\MailInterface;
use App\GaelO\Adapters\SendEmailAdapter;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Constants\MailConstants;

Class MailServices extends SendEmailAdapter {

    public function __construct(MailInterface $mailInterface, UserRepository $userRepository){
        $this->mailInterface = $mailInterface;
        $this->userRepository = $userRepository;
    }

    public function getAdminsEmails(){
        $adminsEmails = $this->userRepository->getAdministratorsEmails();
        return $adminsEmails;
    }

    /**
     * Parameters in associative array : name, email, center, request
     */
    public function sendRequestMessage(array $parameters){
        $adminMails = $this->getAdminsEmails();
        $this->mailInterface->setTo($adminMails);
        $this->mailInterface->setParameters($parameters);
        $this->mailInterface->sendModel(MailConstants::EMAIL_REQUEST);

    }

}
