<?php

namespace App\GaelO\UseCases\GetCountry;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;


class GetCountry {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

    public function execute(GetCountryRequest $countryRequest, GetCountryResponse $countryResponse) : void
    {
        $code = $countryRequest->code;
        try {
            if ($code == 0) $countryResponse->body = $this->persistenceInterface->getAll();
            else $countryResponse->body = $this->persistenceInterface->find($code);
            $countryResponse->status = 200;
            $countryResponse->statusText = 'OK';
        } catch (\Throwable $t) {
            $countryResponse->statusText = $t->getMessage();
            $countryResponse->status = 500;
        }
    }

}

?>
