<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\Models\Tracker;
use App\GaelO\Util;
use Exception;

class TrackerRepository implements PersistenceInterface, TrackerRepositoryInterface {

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
        throw new Exception('Not Allowed for tracker');
    }

    public function delete($id) :void {
        throw new Exception("Tracker Delete Forbidden");
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
        $trackerData = $this->tracker->with('user')->where('study_name', $study)->where("action_type", $action)->get();
        return empty($trackerData) ? [] : $trackerData->toArray();
    }

}
