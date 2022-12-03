<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class MonitorTreeService extends InvestigatorTreeService{

    protected string $role = Constants::ROLE_MONITOR;

    public function buildTree(): array
    {
        if($this->studyEntity->monitorShowAll){
            //If show all in this study, return whole list of visits
            $visitsArray = $this->visitRepositoryInterface->getVisitsInStudy($this->studyEntity->name, false, false, false, null);
            return $this->formatResponse($visitsArray);
        }else{
            //Return only visits belonging to user's centers as done for investigator roles
            return Parent::buildTree();
        }

    }

}
