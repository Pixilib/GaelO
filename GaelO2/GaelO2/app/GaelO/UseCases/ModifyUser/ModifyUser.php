<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Util;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

     public function execute(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {
        //TODO
        $id = $userRequest->id;
        $username = $userRequest->username;
        $user = $this->persistenceInterface->find($id);

        try {
            $this->persistenceInterface->update($user['id'], $data);
            $userResponse->status = 200;
            $userResponse->statusText = 'OK';
        } catch (\Throwable $t) {
            $userResponse->status = 500;
        }
    }

}

?>
