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

    public function isAdmin(int $userId) : bool {
        $userData = $this->userRepository->find($userId);
        return $userData['administrator'] ==true;
    }

    public function isSameUserId(int $currentUserId, int $requestedUserId) {
        if ($currentUserId !== $requestedUserId) throw new GaelOAuthorizationException("Unauthorized");
    }

    public function isRoleAllowed(int $userId, String $role){

    }

    public function isPatientAllowed(int $userId, int $patientCode){

    }

    public function isVisitAllowed(int $userId, int $visitId){

    }

}
