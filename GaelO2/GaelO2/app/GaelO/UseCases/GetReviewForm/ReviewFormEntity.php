<?php

namespace App\GaelO\UseCases\GetReviewForm;

class ReviewFormEntity {
    public int $id;
    public ?string $studyName;
    public int $userId;
    public string $date;
    public ?int $visitId;
    public bool $validated;
    public bool $adjudication;
    public array $data;
    public array $files;
    public ?string $username = null;

    public static function fillFromDBReponseArray(array $array){
        $reviewFormEntity  = new ReviewFormEntity();
        $reviewFormEntity->id = $array['id'];
        $reviewFormEntity->study_name = $array['study_name'];
        $reviewFormEntity->userId = $array['user_id'];
        $reviewFormEntity->date = $array['review_date'];
        $reviewFormEntity->visitId = $array['visit_id'];
        $reviewFormEntity->adjudication = $array['adjudication'];
        $reviewFormEntity->validated = $array['validated'];
        $reviewFormEntity->data = $array['review_data'];
        $reviewFormEntity->files = $array['sent_files'];
        return $reviewFormEntity;
    }

    public function setInvestigatorDetails(string $username){
        $this->username = $username;
    }

}
