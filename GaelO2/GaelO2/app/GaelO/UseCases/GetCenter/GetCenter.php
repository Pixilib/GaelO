<?php

namespace App\GaelO\UseCases\GetCenter;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
     }

    public function execute(GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse) : void
    {
        try{

            $this->checkAuthorization($getCenterRequest->currentUserId);

            $code = $getCenterRequest->code;

            if ($code == -1) {
                $centers = $this->persistenceInterface->getAll();
                $response = [];
                foreach($centers as $center){
                    $response[] = CenterEntity::fillFromDBReponseArray($center);
                }
                $getCenterResponse->body = $response;

            } else {
                $center  = $this->persistenceInterface->getCenterByCode($code);
                $getCenterResponse->body = CenterEntity::fillFromDBReponseArray($center);
            }

            $getCenterResponse->status = 200;
            $getCenterResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $getCenterResponse->status = $e->statusCode;
            $getCenterResponse->statusText = $e->statusText;
            $getCenterResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}

?>
