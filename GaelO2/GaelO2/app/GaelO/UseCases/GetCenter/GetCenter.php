<?php

namespace App\GaelO\UseCases\GetCenter;

use App\GaelO\Interfaces\PersistenceInterface;


class GetCenter {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

    public function execute(GetCenterRequest $centerRequest, GetCenterResponse $centerResponse) : void
    {
        $code = $centerRequest->code;
        if ($code == 0) $centerResponse->body = $this->persistenceInterface->getAll();
        else $centerResponse->body = $this->persistenceInterface->find($code);
        $centerResponse->status = 200;
        $centerResponse->statusText = 'OK';
    }

}

?>
