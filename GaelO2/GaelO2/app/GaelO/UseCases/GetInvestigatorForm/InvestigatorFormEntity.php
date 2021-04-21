<?php

namespace App\GaelO\UseCases\GetInvestigatorForm;

class InvestigatorFormEntity {
    public int $id;
    public ?string $studyName;
    public int $userId;
    public string $username;
    public string $date;
    public ?int $visitId;
    public bool $validated;
    public array $data;

    public static function fillFromDBReponseArray(array $array){
        $investigatorFormEntity  = new InvestigatorFormEntity();
        $investigatorFormEntity->id = $array['id'];
        $investigatorFormEntity->study_name = $array['study_name'];
        $investigatorFormEntity->userId = $array['user_id'];
        $investigatorFormEntity->username = $array['username'];
        $investigatorFormEntity->date = $array['review_date'];
        $investigatorFormEntity->visitId = $array['visit_id'];
        $investigatorFormEntity->validated = $array['validated'];
        $investigatorFormEntity->data = $array['review_data'];
        return $investigatorFormEntity;
    }

}
