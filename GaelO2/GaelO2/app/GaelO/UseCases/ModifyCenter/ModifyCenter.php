<?php

namespace App\GaelO\UseCases\ModifyCenter;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Util;

class ModifyCenter {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

    //logique métier (ex validation ...)
     public function execute(ModifyCenterRequest $centerRequest, ModifyCenterResponse $centerResponse) : void
    {
        $name = $centerRequest->name;
        $this->persistenceInterace->updateCenter($name, $centerRequest->code, $centerRequest->country_code);
        $centerResponse->status = 200;
        $centerResponse->statusText = 'OK';
    }

/*
    if($this->persistenceInterface->isKnownCenter($name)){
        $centerResponse->status = 409;
        $centerResponse->statusText = 'Conflict';
        return;

    };
    */

}

?>
