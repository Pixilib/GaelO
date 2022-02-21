<?php

namespace App\GaelO\Services;

class StudyService {

    private array $studyEntity;

    public function setStudyEntity(array $studyEntity){
        $this->studyEntity = $studyEntity;
    }

    public function isAncillary() : bool {
        return $this->studyEntity['ancillary_of'] !== null;
    }

    /**
     * Return original study name which is
     * Current study if non ancillary
     * Original study if ancillary study
     */
    public function getOriginalStudyName() : string {
        if ($this->studyEntity['ancillary_of']) return $this->studyEntity['ancillary_of'];
        else return $this->studyEntity['name'];
    }

}
