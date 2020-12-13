<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\Tracker;
use App\GaelO\Util;
use Exception;

class TrackerRepository implements PersistenceInterface {

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function update($id, array $data) : void {
        throw new Exception('Tracker Not updatable');
    }

    public function create(array $data){
        $tracker = new Tracker();
        $model = Util::fillObject($data, $tracker);
        $model->save();
    }

    public function find(int $id){
        throw new Exception('Tracker Not Accessible by Item');
    }

    public function getAll() :array {
        $trackers = $this->tracker->with('user')->get();
        return empty($trackers) ? [] : $trackers->toArray();
    }

    public function delete($id) :void {
        throw new Exception("Tracker Delete Forbidden");
    }

    public function getTrackerOfRole(string $role) : array {
        $trackerData = $this->tracker->with('user')->where('role', $role)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOfRoleAndStudy(string $study, string $role) : array{
        $trackerData = $this->tracker->where('study_name', $study)->with('user')->where('role', $role)->get();
        return empty($trackerData)  ? [] : $trackerData->toArray();
    }

    public function getTrackerOfVisitId(int $visitId) : array {
        $trackerData = $this->tracker->where('visit_id', $visitId)->with('user');
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

    public function getTrackerOrActionInStudy(string $action, string $study) : array {
        $trackerData = $this->tracker->where('study_name', $study)->with('user')->where("action_type", $action)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

}
