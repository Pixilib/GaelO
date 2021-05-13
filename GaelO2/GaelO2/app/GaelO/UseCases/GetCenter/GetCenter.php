<?php

namespace App\GaelO\UseCases\GetCenter;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetCenter {

    private CenterRepositoryInterface $centerRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(CenterRepositoryInterface $centerRepositoryInterface, AuthorizationService $authorizationService){
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->authorizationService = $authorizationService;
     }

    public function execute(GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse) : void
    {
        try{

            $this->checkAuthorization($getCenterRequest->currentUserId);

            $code = $getCenterRequest->code;

            if ($code === null) {
                $centers = $this->centerRepositoryInterface->getAll();
                $response = [];
                foreach($centers as $center){
                    $response[] = CenterEntity::fillFromDBReponseArray($center);
                }
                $getCenterResponse->body = $response;

            } else {
                $center  = $this->centerRepositoryInterface->getCenterByCode($code);
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
