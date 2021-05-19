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

    private function create(array $data){
        $tracker = new Tracker();
        $model = Util::fillObject($data, $tracker);
        $model->save();
    }

    public function writeAction(int $userId, string $role, ?string $study, ?int $id_visit, string $actionType, ?array $actionDetails) : void {
        $data = [
            'study_name' => $study,
            'user_id' => $userId,
            'date'=> Util::now(),
            'role' => $role,
            'visit_id'=> $id_visit,
            'action_type' => $actionType,
            'action_details' => json_encode($actionDetails)
        ];

        $this->create($data);
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
