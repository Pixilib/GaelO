<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class VisitUploadEvent extends AbstractGaelOStudyEvent
{
    public function __construct(int $visitId)
    {
        $this->type = GaelOStudyEventEnum::UPLOAD_VISIT_EVENT;
        $this->data = ['visitId' => $visitId];
    }
}
