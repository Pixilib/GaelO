<?php

namespace App\GaelO\UseCases\DeleteUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;

class DeleteUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

     public function execute(DeleteUserRequest $userRequest, DeleteUserResponse $userResponse) : void {
        $this->persistenceInterface->delete($userRequest->id);
        $userResponse->body = 'User deleted';
        $userResponse->status = '200';
        $userResponse->statusText = 'OK';
    }

}

?>
