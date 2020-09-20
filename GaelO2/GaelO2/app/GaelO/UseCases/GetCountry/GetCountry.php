<?php

namespace App\GaelO\UseCases\GetCountry;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetCountry\CountryEntity;
use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;


class GetCountry {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

    public function execute(GetCountryRequest $countryRequest, GetCountryResponse $countryResponse) : void
    {
        $code = $countryRequest->code;
        if ($code == '') {
            $responseArray = [];
            $countries = $this->persistenceInterface->getAll();
            foreach($countries as $country){
                $responseArray[] = CountryEntity::fillFromDBReponseArray($country);
            }
            $countryResponse->body = $responseArray;
        }else {
            $country = $this->persistenceInterface->find($code);
            $countryResponse->body = CountryEntity::fillFromDBReponseArray($country);
        }
        $countryResponse->status = 200;
        $countryResponse->statusText = 'OK';
    }

}

?>
