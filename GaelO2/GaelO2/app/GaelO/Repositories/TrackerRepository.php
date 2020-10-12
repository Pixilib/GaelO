<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\Tracker;
use App\GaelO\Util;

class TrackerRepository implements PersistenceInterface {

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function update($id, array $data) : void {
        $model = $this->tracker->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->tracker);
        $model->save();
    }

    public function find(int $id){
        return $this->tracker->find($id)->toArray();
    }

    public function getAll() :array {
        $trackers = $this->tracker->get();
        return empty($trackers) ? [] : $trackers->toArray();
    }

    public function delete($id) :void {
        throw new GaelOException("Tracker Delete Forbidden");
    }

    public function getTrackerOfRole(string $role) : array {
        $trackerData = $this->tracker->where('role', $role);
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfRoleAndStudy(string $study, string $role) : array{
        $trackerData = $this->tracker->where('study_name', $study)->where('role', $role);
        return empty($trackerData)  ? [] : $trackerData->toArray();
    }

    public function getTrackerOfVisitId(int $visitId) : array {
        $trackerData = $this->tracker->where('visit_id', $visitId);
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getUsersInternalMessageOfStudy(string $study) : array {
        $trackerData = $this->tracker->where('study_name', $study)->where("action_type", Constants::TRACKER_SEND_MESSAGE);
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

}
