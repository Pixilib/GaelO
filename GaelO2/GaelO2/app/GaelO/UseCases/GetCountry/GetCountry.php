<?php

namespace App\GaelO\UseCases\GetCountry;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetCountry\CountryEntity;
use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;
use Exception;

class GetCountry {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
     }

    public function execute(GetCountryRequest $getCountryRequest, GetCountryResponse $getCountryResponse) : void
    {
        try{

            $this->checkAuthorization($getCountryRequest);
            $code = $getCountryRequest->code;
            if ($code == '') {
                $responseArray = [];
                $countries = $this->persistenceInterface->getAll();
                foreach($countries as $country){
                    $responseArray[] = CountryEntity::fillFromDBReponseArray($country);
                }
                $getCountryResponse->body = $responseArray;
            }else {
                $country = $this->persistenceInterface->find($code);
                $getCountryResponse->body = CountryEntity::fillFromDBReponseArray($country);
            }
            $getCountryResponse->status = 200;
            $getCountryResponse->statusText = 'OK';

        }catch (GaelOException $e){

            $getCountryResponse->status = $e->statusCode;
            $getCountryResponse->statusText = $e->statusText;
            $getCountryRequest->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(GetCountryRequest $getCountryRequest){
        $this->authorizationService->setCurrentUser($getCountryRequest->currentUserId);
        if(!$this->authorizationService->isAdmin()){
            throw new GaelOForbiddenException();
        };
    }

}

?>
