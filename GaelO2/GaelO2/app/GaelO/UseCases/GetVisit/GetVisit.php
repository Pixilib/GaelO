<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Interfaces\PersistenceInterface;

class GetVisit {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitTypeResponse){
        $id = $getVisitRequest->visitId;
        $studyName = $getVisitRequest->studyName;
        $GLOBALS['studyName'] = $studyName;
        if ($id == 0) {
            $dbData = $this->persistenceInterface->getAll();
            $dbData = array_filter($dbData, function ($element) {
                return $element['study_name'] == $GLOBALS['studyName'];
            });
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = VisitEntity::fillFromDBReponseArray($data);
            }
            $getVisitRequest->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->find($id);
            $responseEntity = VisitEntity::fillFromDBReponseArray($dbData);
            $getVisitRequest->body = $responseEntity;
        }
        $getVisitTypeResponse->status = 200;
        $getVisitTypeResponse->statusText = 'OK';
    }
}
