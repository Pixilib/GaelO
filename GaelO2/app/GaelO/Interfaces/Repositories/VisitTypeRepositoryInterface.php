<?php

namespace App\GaelO\Interfaces\Repositories;

interface VisitTypeRepositoryInterface
{

    public function find(int $id, bool $withVisitGroup) : array ;

    public function findByName(string $studyName, string $visitGroupName, string $visitTypeName) : array;

    public function delete($id) : void ;

    public function createVisitType(
        int $visitGroupId,
        string $name,
        int $order,
        bool $localFormNeeded,
        int $qcProbability,
        int $reviewProbability,
        bool $optional,
        int $limitLowDays,
        int $limitUpDays,
        string $anonProfile,
        array $dicomConstraints
    ) :void ;

    public function hasVisits(int $visitTypeId): bool ;

    public function isExistingVisitType(int $visitGroupId, string $name): bool ;

    public function getVisitTypesOfStudy(string $studyName) : array ;
}
