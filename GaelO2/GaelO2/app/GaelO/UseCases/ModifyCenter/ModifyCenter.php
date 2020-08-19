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
        try {
            $this->persistenceInterface->getCenterByName($name);
            $centerResponse->status = 200;
            $centerResponse->statusText = 'OK';
        } catch (\Throwable $t) {
            $centerResponse->status = 500;
        }
    }

}

?>
