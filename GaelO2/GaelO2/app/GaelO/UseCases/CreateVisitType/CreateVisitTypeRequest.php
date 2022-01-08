<?php

namespace App\GaelO\UseCases\CreateVisitType;

class CreateVisitTypeRequest {
    public int $currentUserId;
    public String $visitGroupId;
    public String $name;
    public int $order;
    public bool $localFormNeeded;
    public bool $qcProbability;
    public bool $reviewProbability;
    public bool $optional;
    public int $limitLowDays;
    public int $limitUpDays;
    public string $anonProfile;
    public array $dicomConstraints;
}
