<?php

namespace App\GaelO\Services\Mails;

use App\GaelO\Adapters;

Class MailServices extends SendEmailAdapter {

    public function __construct(UserRepository $userRepository){
        $this->userRepository = $userRepository
    }

    addAdminsEmails(){
        $adminsEmails = $this->userRepository->getAdministratorsEmails();
        return $adminsEmails
    }

}