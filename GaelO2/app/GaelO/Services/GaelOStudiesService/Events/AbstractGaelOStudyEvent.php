<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

abstract class AbstractGaelOStudyEvent
{
    public GaelOStudyEventEnum $type;
    public array $data;
}