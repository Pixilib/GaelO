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

        if ($id == 0) {
            $dbData = $this->persistenceInterface->getAll();
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = UserEntity::fillFromDBReponseArray($data);
            }
            $userResponse->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->find($id);
            $responseEntity = UserEntity::fillFromDBReponseArray($dbData);
            $userResponse->body = $responseEntity;
        }
        $userResponse->status = 200;
        $userResponse->statusText = 'OK';

    }

}

?>
