<?php

namespace App\GaelO\Interfaces\Repositories;

interface TrackerRepositoryInterface {

    public function getTrackerOfRole(string $role) : array ;

    public function getTrackerOfRoleAndStudy(string $studyName, string $role, bool $withUser) : array ;

    public function getTrackerOfVisitId(int $visitId, string $studyName) : array ;

    public function writeAction(int $userId, string $role, ?string $studyName, ?int $id_visit, string $actionType, array $actionDetails) : void ;

    public function getTrackerOfRoleActionInStudy(string $role, string $action, string $studyName) : array ;

    public function getTrackerOfMessages(string $studyName) : array ;

}
