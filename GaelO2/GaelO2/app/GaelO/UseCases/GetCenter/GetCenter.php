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

        if ($code == -1) {
            $centers = $this->persistenceInterface->getAll();
            $response = [];
            foreach($centers as $center){
                $response[] = CenterEntity::fillFromDBReponseArray($center);
            }
            $centerResponse->body = $response;

        } else {
            $center  = $this->persistenceInterface->getCenterByCode($code);
            $centerResponse->body = CenterEntity::fillFromDBReponseArray($center);
        }

        $centerResponse->status = 200;
        $centerResponse->statusText = 'OK';
    }

}

?>
