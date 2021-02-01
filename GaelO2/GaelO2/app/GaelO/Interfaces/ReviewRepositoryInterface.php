<?php

namespace App\GaelO\Interfaces;

interface ReviewRepositoryInterface {

    public function find($id) : array ;

    public function delete($id) : void ;

    public function getInvestigatorForm(int $visitId) : array ;

    public function unlockInvestigatorForm(int $visitId) : void ;

}
