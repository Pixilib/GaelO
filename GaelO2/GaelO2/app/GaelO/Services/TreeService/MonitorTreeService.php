<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class MonitorTreeService extends AbstractTreeService{

    protected string $role = Constants::ROLE_MONITOR;

    public function buildTree(): array
    {
        $visitsArray = $this->visitRepository->getVisitsInStudy($this->studyName, false, false);
        return  $this->makeTreeFromVisits($visitsArray);
    }

}
