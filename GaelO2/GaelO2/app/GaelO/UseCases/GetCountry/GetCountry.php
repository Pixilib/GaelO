<?php

namespace App\GaelO\UseCases\GetCountry;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;


class GetCountry {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

    public function execute(GetCountryRequest $centerRequest, GetCountryResponse $centerResponse) : void
    {
        $code = $centerRequest->code;
        try {
            if ($code == '') $centerResponse->body = $this->persistenceInterface->getAll();
            else $centerResponse->body = $this->persistenceInterface->find($code);
            $centerResponse->status = 200;
            $centerResponse->statusText = 'OK';
        } catch (\Throwable $t) {
            $centerResponse->statusText = $t->getMessage();
            $centerResponse->status = 500;
        }
    }

}

?>
