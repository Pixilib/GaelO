<?php

namespace App\GaelO\Interfaces;

interface ReviewRepositoryInterface {

    public function getInvestigatorForm(int $visitId) : array ;

    public function unlockInvestigatorForm(int $visitId) : void ;

}
