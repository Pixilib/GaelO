<?php

namespace App\GaelO\Entities;

class ReviewEntity
{
    public int $id;
    public bool $local;
    public string $studyName;
    public int $userId;
    public string $date;
    public int $visitId;
    public bool $validated;
    public array $data;
    public array $files;
    public bool $adjudication;
    public UserEntity $user;

    public static function fillFromDBReponseArray(array $array): ReviewEntity
    {
        $reviewEntity  = new ReviewEntity();
        $reviewEntity->local = $array['local'];
        $reviewEntity->id = $array['id'];
        $reviewEntity->study_name = $array['study_name'];
        $reviewEntity->userId = $array['user_id'];
        $reviewEntity->date = $array['review_date'];
        $reviewEntity->visitId = $array['visit_id'];
        $reviewEntity->validated = $array['validated'];
        $reviewEntity->data = $array['review_data'];
        $reviewEntity->files = $array['sent_files'];
        $reviewEntity->adjudication = $array['adjudication'];
        return $reviewEntity;
    }

    public function setUserDetails(UserEntity $userEntity): void
    {
        $this->user = $userEntity;
    }
}
