<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Repositories\StudyRepository;

class AuthorizationStudyService
{
    private int $studyName;
    private array $studyData;
    private StudyRepository $studyRepository;

    public function __construct(StudyRepository $studyRepository ) {
        $this->studyRepository = $studyRepository;
    }

    public function setStudyName(string $studyName)
    {
        $this->studyName = $studyName;
    }

    private function fillStudyData(){
        if($this->studyData == null) $this->studyData = $this->studyRepository->find($this->studyName);
    }


    public function isAncillaryStudy(): bool
    {
        $this->fillStudyData();
        return $this->studyData['ancillary_of'] == null ? false : true;
    }



}
