<?php

namespace App\GaelO\Interfaces;

interface VisitTypeRepositoryInterface
{

    public function createVisitType(
        int $visitGroupId,
        String $name,
        int $visitOrder,
        bool $localFormNeeded,
        bool $qcNeeded,
        bool $reviewNeeded,
        bool $optional,
        int $limitLowDays,
        int $limitUpDays,
        String $anonProfile
    ) :void ;

    public function hasVisits(int $visitTypeId): bool ;

    public function isExistingVisitType(int $visitGroupId, String $name): bool ;
}
