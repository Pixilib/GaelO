<?php

namespace App\GaelO\UseCases\GetTracker;

class TrackerEntity {
    public int $id;
    public ?string $study_name;
    public int $user_id;
    public string $date;
    public string $role;
    public ?int $visit_id;
    public string $action_type;
    public ?string $action_details;

    public ?string $username;
    public ?string $lastname;
    public ?string $firstname;

    public static function fillFromDBReponseArray(array $array){
        $trackerEntity  = new TrackerEntity();
        $trackerEntity->id = $array['id'];
        $trackerEntity->study_name = $array['study_name'];
        $trackerEntity->user_id = $array['user_id'];
        $trackerEntity->date = $array['date'];
        $trackerEntity->role = $array['role'];
        $trackerEntity->visit_id = $array['visit_id'];
        $trackerEntity->action_type = $array['action_type'];
        $trackerEntity->action_details = $array['action_details'];
        return $trackerEntity;
    }

    public function setUserData(array $array){
        $this->username = $array['username'];
        $this->lastname = $array['lastname'];
        $this->firstname = $array['firstname'];
    }

}
