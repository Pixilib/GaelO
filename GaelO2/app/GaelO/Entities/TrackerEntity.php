<?php

namespace App\GaelO\Entities;

class TrackerEntity
{
    public int $id;
    public ?string $study_name;
    public int $user_id;
    public string $date;
    public string $role;
    public ?int $visit_id;
    public string $action_type;
    public ?string $action_details;

    public UserEntity $user;

    public static function fillFromDBReponseArray(array $array)
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

    public function setUserData(array $array)
    {
        $this->user = new UserEntity();
        $this->user->lastname = $array['lastname'];
        $this->user->firstname = $array['firstname'];
        $this->user->email = $array['email'];
    }

    public function setVisitData(array $array)
    {
        $this->visit = new VisitEntity();
        $this->visit->setVisitContext(
            $array['visit_type']['visit_group'],
            $array['visit_type']
        );
        $this->visit->fillFromDBReponseArray($array);
        $this->visit->setReviewVisitStatus($array['review_status']['review_status'], $array['review_status']['review_conclusion_value'], $array['review_status']['review_conclusion_date'], $array['review_status']['target_lesions']);
    }
}
