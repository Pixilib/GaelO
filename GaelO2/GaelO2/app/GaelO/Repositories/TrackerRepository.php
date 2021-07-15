<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\Models\Tracker;
use App\GaelO\Util;

class TrackerRepository implements TrackerRepositoryInterface {

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function writeAction(int $userId, string $role, ?string $study, ?int $id_visit, string $actionType, ?array $actionDetails) : void {
        $tracker = new Tracker();

        $tracker->study_name = $study;
        $tracker->user_id = $userId;
        $tracker->date= Util::now();
        $tracker->role = $role;
        $tracker->visit_id= $id_visit;
        $tracker->action_type = $actionType;
        $tracker->action_details = json_encode($actionDetails);

        $tracker->save();
    }

    public function getTrackerOfRole(string $role) : array {
        $trackerData = $this->tracker->with('user')->where('role', $role)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfRoleAndStudy(string $study, string $role) : array{
        $trackerData = $this->tracker->with('user')->where('study_name', $study)->where('role', $role)->get();
        return empty($trackerData)  ? [] : $trackerData->toArray();
    }

    public function getTrackerOfVisitId(int $visitId) : array {
        $trackerData = $this->tracker->with('user')->where('visit_id', $visitId)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfActionInStudy(string $action, string $study) : array {
        $trackerData = $this->tracker->with('user')->where('study_name', $study)->where('action_type', $action)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfRoleActionInStudy(string $role, string $action, string $study) : array {
        $trackerData = $this->tracker->with('user')->where('study_name', $study)->where('role', $role)->where('action_type', $action)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

}
