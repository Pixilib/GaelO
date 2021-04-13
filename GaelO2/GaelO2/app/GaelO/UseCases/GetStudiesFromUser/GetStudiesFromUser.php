<?php

namespace App\GaelO\UseCases\GetStudiesFromUser;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\UserRepositoryInterface;
use Exception;

class GetStudiesFromUser {

    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(GetStudiesFromUserRequest $getStudiesFromUserRequest, GetStudiesFromUserResponse $getStudiesFromUserResponse)
    {

        try{
            $this->checkAuthorization($getStudiesFromUserRequest->currentUserId, $getStudiesFromUserRequest->userId);

            $studiesEntities = $this->userRepositoryInterface->getStudiesOfUser($getStudiesFromUserRequest->userId);

            $studiesNames = array_map(function ($study){
                return $study['name'];
            }, $studiesEntities);

            $getStudiesFromUserResponse->body = $studiesNames;
            $getStudiesFromUserResponse->status = 200;
            $getStudiesFromUserResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getStudiesFromUserResponse->body = $e->getErrorBody();
            $getStudiesFromUserResponse->status = $e->statusCode;
            $getStudiesFromUserResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $userId) : void
    {
        if($currentUserId !== $userId) throw new GaelOForbiddenException();
    }
}
