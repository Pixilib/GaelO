<?php

namespace App\GaelO\UseCases\GetUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;


class GetUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

    public function execute(GetUserRequest $userRequest, GetUserResponse $userResponse) : void
    {   
        $id = $userRequest->id;
        if ($id == 0) $this->persistenceInterface->getAllUsers();
        $userResponse->body = $this->persistenceInterface->find($id);
        if($id == true) $userResponse->status = 200;
    }

}

?>