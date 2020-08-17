<?php

namespace App\GaelO\Services\Mails;

use App\GaelO\Adapters;

Class MailServices extends SendEmailAdapter {

    public function __construct(UserRepository $userRepository){
        $this->userRepository = $userRepository
    }

    getAdminsEmails(){
        $userAdministratorsArray = $this->userRepository->getAdministrators();
        //SK A VOIR
        $userAdministratorsCollection = new CollectionAdapter($userAdministratorsArray);
        return $userAdministratorsCollection->get('email');
    } 

}