<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

abstract class BaseStudyEvent
{
    public GaelOStudyEventEnum $type;
    protected string $studyName;

    public function __construct(GaelOStudyEventEnum $type, string $studyName)
    {
        $this->type = $type;
        $this->studyName = $studyName;
    }
}
