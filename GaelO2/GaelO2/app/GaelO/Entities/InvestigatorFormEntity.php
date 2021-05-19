<?php

namespace App\GaelO\Entities;

class InvestigatorFormEntity {
    public int $id;
    public string $studyName;
    public int $userId;
    public ?string $username = null;
    public string $date;
    public int $visitId;
    public bool $validated;
    public array $data;
    public array $files;

    public static function fillFromDBReponseArray(array $array){
        $investigatorFormEntity  = new InvestigatorFormEntity();
        $investigatorFormEntity->id = $array['id'];
        $investigatorFormEntity->study_name = $array['study_name'];
        $investigatorFormEntity->userId = $array['user_id'];
        $investigatorFormEntity->date = $array['review_date'];
        $investigatorFormEntity->visitId = $array['visit_id'];
        $investigatorFormEntity->validated = $array['validated'];
        $investigatorFormEntity->data = $array['review_data'];
        $investigatorFormEntity->files = $array['sent_files'];
        return $investigatorFormEntity;
    }

    public function setInvestigatorDetails(string $username){
        $this->username = $username;
    }

}
