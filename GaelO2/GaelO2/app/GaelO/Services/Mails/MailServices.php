<?php

namespace App\GaelO\Services\Mails;

use App\GaelO\Adapters\SendEmailAdapter;
use App\GaelO\Repositories\UserRepository;

Class MailServices extends SendEmailAdapter {

    public function __construct(UserRepository $userRepository){
        $this->userRepository = $userRepository;
    }

    public function addAdminsEmails(){
        $adminsEmails = $this->userRepository->getAdministratorsEmails();
        return $adminsEmails;
    }

}
