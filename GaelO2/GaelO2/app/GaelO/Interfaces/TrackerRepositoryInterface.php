<?php

namespace App\GaelO\Interfaces;

interface TrackerRepositoryInterface {

    public function getTrackerOfRole(string $role) : array ;

    public function getTrackerOfRoleAndStudy(string $study, string $role) : array ;

    public function getTrackerOfVisitId(int $visitId) : array ;

    public function getTrackerOfActionInStudy(string $action, string $study) : array ;

}
