<?php

namespace App\GaelO\Services;

use App\GaelO\Exceptions\GaelOAuthorizationException;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\UseCases\GetUser\UserEntity;

class AuthorizationService {


    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
	}

    //SK A DESIGNER
    //Peut etre besoin d'un GaelOAuthorizationExecption (pour lancer un unauthorized)
    //Appel via des statiques ?

    public function isAdmin(int $userId){
        $userData = $this->userRepository->find($userId);
        $userEntity = UserEntity::fillFromDBReponseArray($userData);
        if( ! $userEntity->administrator) throw new GaelOAuthorizationException("Not Admin");
    }

    public function isRoleAllowed(){

    }

    public function isPatientAllowed(){

    }

    public function isVisitAllowed(){

    }

}
