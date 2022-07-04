<?php

namespace App\GaelO\UseCases\CreateVisitType;

class CreateVisitTypeRequest
{
    public int $currentUserId;
    public String $visitGroupId;
    public String $name;
    public int $order;
    public bool $localFormNeeded;
    public int $qcProbability;
    public int $reviewProbability;
    public bool $optional;
    public int $limitLowDays;
    public int $limitUpDays;
    public string $anonProfile;
    public array $dicomConstraints;
}
