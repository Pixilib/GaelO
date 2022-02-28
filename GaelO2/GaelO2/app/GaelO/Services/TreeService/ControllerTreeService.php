<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class ControllerTreeService extends AbstractTreeService{

    protected string $role = Constants::ROLE_CONTROLLER;

    public function buildTree(): array
    {
        $visitsArray = $this->visitRepository->getVisitsInStudyAwaitingControllerAction($this->studyName);
        return  $this->makeTreeFromVisits($visitsArray);
    }

}
