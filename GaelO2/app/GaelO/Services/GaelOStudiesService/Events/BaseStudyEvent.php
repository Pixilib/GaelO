<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

abstract class BaseStudyEvent
{
    public GaelOStudyEventEnum $type;

    public function __construct(GaelOStudyEventEnum $type)
    {
        $this->type = $type;
    }
}
