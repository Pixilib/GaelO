<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class ControllerTreeService extends AbstractTreeService{

    protected string $role = Constants::ROLE_CONTROLLER;

    public function buildTree(): array
    {
        if($this->studyEntity->controllerShowAll){
            //If show all in this study, return all visits with Needed QC to display all status
            $visitsArray = $this->visitRepositoryInterface->getVisitsInStudyNeedingQualityControl($this->studyEntity->name);
            return $this->makeTreeFromVisits($visitsArray);
        }else{
            //Return only visits awaiting QC
            $visitsArray = $this->visitRepositoryInterface->getVisitsInStudyAwaitingControllerAction($this->studyEntity->name);
            return $this->makeTreeFromVisits($visitsArray);
        }

    }

}
