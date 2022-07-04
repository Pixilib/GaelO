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
        $this->visit->setVisitContext(
            $array['visit_type']['visit_group'],
            $array['visit_type']
        );
        $this->visit->fillFromDBReponseArray($array);
        $this->visit->setReviewVisitStatus($array['review_status']['review_status'], $array['review_status']['review_conclusion_value'], $array['review_status']['review_conclusion_date'], $array['review_status']['target_lesions']);
    }
}
