<?php

namespace App\GaelO\Entities;

class TrackerEntity
{
    public int $id;
    public ?string $study_name;
    public int $userId;
    public string $date;
    public string $role;
    public ?int $visitId;
    public string $actionType;
    public array $actionDetails;

    public UserEntity $user;
    public VisitEntity $visit;

    public static function fillFromDBReponseArray(array $array) : TrackerEntity
    {
        $trackerEntity  = new TrackerEntity();
        $trackerEntity->id = $array['id'];
        $trackerEntity->study_name = $array['study_name'];
        $trackerEntity->userId = $array['user_id'];
        $trackerEntity->date = $array['date'];
        $trackerEntity->role = $array['role'];
        $trackerEntity->visitId = $array['visit_id'];
        $trackerEntity->actionType = $array['action_type'];
        $trackerEntity->actionDetails = $array['action_details'];
        return $trackerEntity;
    }

    public function setUserDetails(UserEntity $userEntity) : void
    {
        $this->user = $userEntity;
    }

    public function setVisitData(array $array) : void
    {
        $this->visit = new VisitEntity();
        $this->visit->patient = new PatientEntity();
        $this->visit->patient->code = $array['patient']['code'];
        $this->visit->patient->id = $array['patient_id'];
        $this->visit->deletedAt = $array['deleted_at'];
        $this->visit->setVisitContext(
            $array['visit_type']['visit_group'],
            $array['visit_type']
        );
        $this->visit->fillFromDBReponseArray($array);
    }
}
