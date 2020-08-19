<?php

namespace App\GaelO\UseCases\CreateCenter;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreateCenter\CreateCenterRequest;
use App\GaelO\UseCases\CreateCenter\CreateCenterResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Util;

class CreateCenter {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

    //logique métier (ex validation ...)
     public function execute(CreateCenterRequest $centerRequest, CreateCenterResponse $centerResponse) : void
    {



    }

}

?>
