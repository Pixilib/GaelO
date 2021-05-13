<?php

namespace App\GaelO\Interfaces\Repositories;

interface TrackerRepositoryInterface {

    public function getTrackerOfRole(string $role) : array ;

    public function getTrackerOfRoleAndStudy(string $study, string $role) : array ;

    public function getTrackerOfVisitId(int $visitId) : array ;

    public function getTrackerOfActionInStudy(string $action, string $study) : array ;

    public function writeAction(int $userId, string $role, ?string $study, ?int $id_visit, string $actionType, ?array $actionDetails) : void ;

    public function getTrackerOfRoleActionInStudy(string $role, string $action, string $study) : array ;

}
