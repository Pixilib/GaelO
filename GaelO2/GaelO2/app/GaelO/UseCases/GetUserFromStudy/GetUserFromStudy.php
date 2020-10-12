<?php

namespace App\GaelO\UseCases\GetUserFromStudy;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetUser\UserEntity;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyRequest;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyResponse;

class GetUserFromStudy {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetUserFromStudyRequest $userRequest, GetUserFromStudyResponse $userResponse) : void
    {
        $studyName = $userRequest->studyName;
        //$userId = $userRequest->userId;
        $dbData = $this->persistenceInterface->getUsersFromStudy($studyName);
        $responseArray = [];
        foreach($dbData as $data){
            $data = $data->toArray();
            $responseArray[] = UserEntity::fillFromDBReponseArray($data);
        }
        $userResponse->body = $responseArray;
        $userResponse->status = 200;
        $userResponse->statusText = 'OK';
    }

}

?>
