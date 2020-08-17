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
        try {
            if ($id == 0) $userResponse->body = $this->persistenceInterface->getAll();
            else $userResponse->body = $this->persistenceInterface->find($id);
            $userResponse->status = 200;
            $userResponse->statusText = 'OK';
        } catch (\Throwable $t) {
            $userResponse->body = $t->getMessage();
            $userResponse->status = 500;
        }
    }

}

?>