<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class AwaitingAdjudicationEvent extends BaseVisitEvent
{

    public function __construct(array $visitEntity)
    {
        parent::__construct(GaelOStudyEventEnum::AWAITING_ADJUDICATION_EVENT, $visitEntity);
    }
}
