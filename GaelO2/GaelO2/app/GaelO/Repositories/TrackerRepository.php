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

    public function update($id, array $data){
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

    public function getAll() {
        return $this->tracker->get()->toArray();
    }

    public function delete($id){
        throw new GaelOException("Tracker Delete Forbidden");
    }

    public function getTrackerOfRole(string $role){
        $trackerData = $this->tracker->where('role', $role);
        return $trackerData->toArray();
    }

    public function getTrackerOfRoleAndStudy(string $study, string $role){
        $trackerData = $this->tracker->where('study_name', $study)->where('role', $role);
        return $trackerData->toArray();
    }

    public function getTrackerOfVisitId(int $visitId){
        $trackerData = $this->tracker->where('visit_id', $visitId);
        return $trackerData->toArray();
    }

    public function getUsersInternalMessageOfStudy(string $study){
        $trackerData = $this->tracker->where('study_name', $study)->where("action_type", Constants::TRACKER_SEND_MESSAGE);
        return $trackerData->toArray();
    }

}
